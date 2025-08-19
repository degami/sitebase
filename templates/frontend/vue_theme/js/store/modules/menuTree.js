import gql from 'graphql-tag';

const state = () => ({
    menuTree: [],
    loading: false,
});
  
const mutations = {
    setMenuTree(state, menuTree) {
        state.menuTree = menuTree;
    },
    setLoading(state, loading) {
        state.loading = loading;
    },
};
  
const actions = {
    async fetchMenuTree({ commit, dispatch }, props ) {
        let menuName = props.menuName;
        let websiteId = props.websiteId;
        let maxLevels = props.maxLevels || 3;

        let queryLevels = "...MenuItemFields";
        for ( let i=0; i < Math.abs(maxLevels); i++) {
            queryLevels = `
                ...MenuItemFields
                children {
                    `+queryLevels+`
                }
`;
        }

        let completeQuery = `
        query menuTree($menu_name: String!, $website_id: Int!) {
            menuTree(menu_name: $menu_name, website_id: $website_id) {
               `+queryLevels+`
            }
        }
        
        fragment MenuItemFields on Menu {
            id
            parent_id
            rewrite_id
            title
            locale
            href
            target
            internal_route
            breadcrumb
            level
        }
`;

        const MENU_QUERY = gql(completeQuery);


        const QUERY_VARIABLES = {
            "menu_name": menuName,
            "website_id": websiteId,
        };

        commit('setLoading', true);  // Imposta loading a true quando inizia il fetch

        try {
            const client = await dispatch('apolloClient/getApolloClient', null, { root: true });  // Usa il root per accedere a un modulo Vuex diverso
            if (!client) {
              throw new Error("Apollo Client non inizializzato");
            }

            const { data } = await client.query({
                query: MENU_QUERY,
                variables: QUERY_VARIABLES
            });
            commit('setMenuTree', data.menuTree);
        } catch (error) {
            console.error('Errore durante il fetch del menu:', error);
        } finally {
            commit('setLoading', false);  // Imposta loading a false quando il fetch è completato
        }
    },
    async findMenuItemByPath({state, dispatch}, {path, menu_name = null, website_id = null, maxLevels = 3}) {
        if (!state.menuTree.length) {
            if (null == menu_name || null == website_id) {
                return null;
            }

            await dispatch('fetchMenuTree', {"menuName": menu_name, "websiteId": website_id, 'maxLevels': maxLevels});
        }

        // Funzione ricorsiva per trovare l'elemento con il path
        const findInTree = (tree, targetPath) => {
            for (let item of tree) {
                if (item.internal_route === targetPath) {
                    return item;  // Se il percorso corrisponde, ritorniamo l'elemento
                }

                if (item.children && item.children.length) {
                    // Cerchiamo ricorsivamente nei figli
                    const found = findInTree(item.children, targetPath);
                    if (found) {
                        return found;
                    }
                }
            }
            return null; // Se non troviamo nulla, ritorniamo null
        };

        // Cerchiamo l'elemento nel menuTree
        const foundItem = findInTree(state.menuTree, path);

        return foundItem; // Ritorniamo l'elemento trovato, oppure null se non c'è
    }
};
  
export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
