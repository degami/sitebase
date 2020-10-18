<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Models;

use \App\Base\Abstracts\Models\BaseModel;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use \Psr\Container\ContainerInterface;
use \App\Base\Traits\WithOwnerTrait;

/**
 * Contact Submission Model
 *
 * @method int getId()
 * @method int getContactId()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class ContactSubmission extends BaseModel
{
    use WithOwnerTrait;

    /**
     * gets submission Contact Form
     *
     * @return Contact
     * @throws Exception
     */
    public function getContact()
    {
        $this->checkLoaded();

        return $this->getContainer()->make(Contact::class, ['dbrow' => $this->contact()->fetch()]);
    }

    /**
     * submit data
     *
     * @param ContainerInterface $container
     * @param array $submission_data
     * @return self
     */
    public static function submit(ContainerInterface $container, $submission_data = [])
    {
        $contact_submission = $container->get(ContactSubmission::class);
        $contact_submission->contact_id = $submission_data['contact_id'];
        $contact_submission->user_id = $submission_data['user_id'];
        $contact_submission->persist();

        foreach ($submission_data['data'] as $data) {
            $contact_submission_data_row = $container->get('db')->createRow('contact_submission_data');
            $data['contact_submission_id'] = $contact_submission->id;

            $contact_submission_data_row->update($data);
        }

        return $contact_submission;
    }

    /**
     * gets full key => value pairs data
     *
     * @return array
     * @throws BasicException
     */
    public function getFullData()
    {
        $data = $this->getData();
        $data['user'] = $this->getContainer()->call([User::class, 'load'], ['id' => $data['user_id']])->getData();
        $values = array_map(
            function ($el) {
                $field_label = $el->contact_definition()->fetch()->field_label;
                $field_value = $el->field_value;
                return [$field_label => $field_value];
            },
            $this->getDb()->table('contact_submission_data')->where(['contact_submission_id' => $this->id])->fetchAll()
        );

        $data['values'] = [];
        foreach ($values as $row) {
            foreach ($row as $key => $value) {
                $data['values'][$key] = $value;
            }
        }
        return $data;
    }
}
