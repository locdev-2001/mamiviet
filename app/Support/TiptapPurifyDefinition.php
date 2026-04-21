<?php

namespace App\Support;

use HTMLPurifier_HTMLDefinition;
use Stevebauman\Purify\Definitions\Definition;
use Stevebauman\Purify\Definitions\Html5Definition;

class TiptapPurifyDefinition implements Definition
{
    public static function apply(HTMLPurifier_HTMLDefinition $definition): void
    {
        Html5Definition::apply($definition);

        $definition->addAttribute('div', 'data-type', 'Text');
        $definition->addAttribute('div', 'data-label', 'Text');
        $definition->addAttribute('div', 'data-youtube-video', 'Text');
        $definition->addAttribute('div', 'data-vimeo-video', 'Text');
        $definition->addAttribute('div', 'data-native-video', 'Text');
        $definition->addAttribute('span', 'data-type', 'Text');

        $definition->addAttribute('iframe', 'allow', 'Text');
        $definition->addAttribute('iframe', 'allowfullscreen', 'Bool');
        $definition->addAttribute('iframe', 'frameborder', 'Text');
        $definition->addAttribute('iframe', 'data-aspect-width', 'Text');
        $definition->addAttribute('iframe', 'data-aspect-height', 'Text');

        $definition->addAttribute('img', 'loading', 'Enum#lazy,eager');

        $definition->addAttribute('video', 'autoplay', 'Bool');
        $definition->addAttribute('video', 'loop', 'Bool');
        $definition->addAttribute('video', 'muted', 'Bool');
        $definition->addAttribute('video', 'playsinline', 'Bool');

        $definition->addElement('details', 'Block', 'Flow', 'Common', [
            'open' => 'Bool',
        ]);
        $definition->addElement('summary', 'Block', 'Flow', 'Common');
    }
}
