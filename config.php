<?php

require_once INCLUDE_DIR . 'class.plugin.php';
include_once(INCLUDE_DIR . 'class.dept.php');
include_once(INCLUDE_DIR . 'class.list.php');


class SlackPluginConfig extends PluginConfig {
    function getOptions() {
        return array(
            'slack' => new SectionBreakField(array(
                'label' => 'Slack',
            )),
            'slack-webhook-url' => new TextboxField(array(
                'label' => 'Webhook URL',
                'configuration' => array(
                    'size' => 100,
                    'length' => 200,
                ),
            )),
            'slack-text-length' => new TextboxField(array(
                'label' => 'Tamanho do texto na notificação',
                'configuration' => array(
                    'size' => 10,
                    'length' => 10,
                ),
            )),
        );
    }
}
