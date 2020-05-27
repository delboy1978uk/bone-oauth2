<?php

namespace Bone\OAuth2\Form;

use Del\Form\Form;
use Del\Form\Field\Radio;
use Del\Form\Field\Submit;
use Del\Form\Field\Text;
use Del\Form\Field\TextArea;
use Del\Form\Renderer\HorizontalFormRenderer;
use Del\Form\Validator\Adapter\ValidatorAdapterZf;
use Laminas\Validator\Uri;

class RegisterClientForm extends Form
{
    public function init()
    {
        $redirect = new Text('redirect_uris');
        $redirect->setRequired(true);
        $validator = new ValidatorAdapterZf(new Uri());
        $redirect->addValidator($validator);

        $name = new Text('client_name');
        $name->setRequired(true);

        $method = new Text('token_endpoint_auth_method');
        $method->setRequired(true);

        $logo = new Text('logo_uri');
        $logo->setRequired(true);
        $validator = new ValidatorAdapterZf(new Uri());
        $logo->addValidator($validator);

        $this->addField($name);
        $this->addField($redirect);
        $this->addField($method);
        $this->addField($logo);
    }
}
