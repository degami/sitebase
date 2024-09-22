import axios from 'axios';

const api = axios.create({
  baseURL: 'https://api.example.com', // URL del tuo endpoint
  headers: {
    'Content-Type': 'application/json',
  },
});

export default api;