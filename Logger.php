<?php
/**
 * User: zura
 * Date: 10/23/18
 * Time: 11:30 AM
 */

namespace apollo11\slacklogger;

/**
 * Class Logger
 *
 * @author Zura Sekhniashvili <zurasekhniashvili@gmail.com>
 * @package apollo11\slacklogger
 */
class Logger
{
    private $webhookUrl = null;
    public $debugMode = false;
    public $mentionChannelMembers = false;

    public function __construct($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function send($title, $message, $level = 'error')
    {
        $response = $this->makeRequest($this->webhookUrl, [
            "text" => $title,
            'attachments' => [
                [
                    'fallback' => 'Required plain-text summary of the attachment.',
                    'color' => 'red',
//                    'title' => $title,
                    'text' => ($this->mentionChannelMembers ? '<!channel>' : '') . '```' . PHP_EOL . $this->formatMessage($message) . PHP_EOL . '```',
                    'fields' => [
                        [
                            'title' => 'Level',
                            'value' => '`' . ucfirst($level) . '`',
                            'short' => true,
                        ],
                        [
                            'title' => 'Datetime',
                            'value' => '`' . date('Y-m-d H:i:s') . '`',
                            'short' => true,
                        ]
                    ],
                ]
            ]
        ]);
        if (isset($response['http_code']) && $response['http_code'] === 200) {
            return true;
        }
    }

    public function formatMessage($message)
    {
        $trace = debug_backtrace();
        unset($trace[0]);
        $traceItems = [];
        foreach ($trace as $item) {
            $traceItems[] = $item['file'].'. Line: '.$item['line'];
        }
        return $message.PHP_EOL.PHP_EOL."Trace".PHP_EOL.implode('\n', $traceItems);
    }

    private function makeRequest($url, $data = [])
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . mb_strlen($data)
        ];

        // create curl resource
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($this->debugMode) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        }

        curl_exec($ch);
        $response = curl_getinfo($ch);

        curl_close($ch);
        return $response;
    }
}