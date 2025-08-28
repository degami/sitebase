<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Models;

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\GraphQl\GraphQLExport;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use Exception;

/**
 * Contact Field Definition Model
 *
 * @method int getId()
 * @method int getContactId()
 * @method string getFieldType()
 * @method string getFieldLabel()
 * @method boolean getFieldRequired()
 * @method string getFieldData()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setContactId(int $contact_id)
 * @method self setFieldType(string $field_type)
 * @method self setFieldLanel(string $field_label)
 * @method self setFieldRequired(string $field_required)
 * @method self setFieldData(string $field_data)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class ContactDefinition extends BaseModel
{
}
