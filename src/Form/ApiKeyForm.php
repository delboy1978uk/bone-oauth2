<?php

declare(strict_types=1);

namespace Bone\OAuth2\Form;

use Bone\I18n\Form;
use Del\Form\Field\Radio;
use Del\Form\Field\Submit;
use Del\Form\Field\Text;
use Del\Form\Field\TextArea;
use Del\Form\Renderer\HorizontalFormRenderer;

class ApiKeyForm extends Form
{
    public function init(): void
    {
        $name = new Text('name');
        $name->setRequired(true);
        $name->setLabel('App name');

        $description = new TextArea('description');
        $description->setRequired(true);
        $description->setLabel('App Description');

        $icon = new Text('icon');
        $icon->setRequired(true);
        $icon->setLabel('App Icon');

        $callbackUrls = new TextArea('callbackUrls');
        $callbackUrls->setRequired(true);
        $callbackUrls->setLabel('Callback URLs');
        $callbackUrls->setAttribute('placeholder', 'Enter one URL per line or comma-separated');
        $callbackUrls->setAttribute('rows', '3');

        $radio = new Radio('grantType');
        $radio->setRequired(true);
        $radio->setLabel('Select a Grant Type');
        $radio->setRenderInline(true);
        $radio->setOptions([
            'client_credentials' => 'client_credentials',
            'authorization_code' => 'authorization_code',
        ]);

        $radio2 = new Radio('confidential');
        $radio2->setRequired(true);
        $radio2->setLabel('Where does this app run?');
        $radio2->setRenderInline(true);
        $radio2->setOptions([
            'confidential' => 'Server side App',
            'public' => 'JS or Smart device app',
        ]);

        $submit = new Submit('submit');
        $submit->setValue('Submit');
        $submit->setLabel('Submit');

        $this->addField($name);
        $this->addField($description);
        $this->addField($icon);
        $this->addField($callbackUrls);
        $this->addField($radio);
        $this->addField($radio2);
        $this->addField($submit);
        $this->setFormRenderer(new HorizontalFormRenderer());
    }
}
