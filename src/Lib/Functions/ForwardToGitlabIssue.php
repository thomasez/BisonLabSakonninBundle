<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/*
 * If you want to use this, you have to add the environment variables mentioned
 * in the constructor.
 */
class ForwardToGitlabIssue
{
    use CommonFunctions;

    public $callback_functions = [
    ];

    public $forward_functions = [
        'ForwardToGitlab' => array(
            'description' => "On message creation, send the subject and content to a pre defined Gitlab project as an issue",
            'attribute_spec' => "Project ID",
            'needs_attributes' => true,
        ),
    ];

    private ?string $gitlab_url;
    private ?string $gitlab_access_token;
    private ?string $gitlab_project_id;
    private ?string $gitlab_labels;

    public function __construct(
    ) {
        $this->gitlab_url = $_ENV['GITLAB_URL'] ?? null;
        $this->gitlab_access_token = $_ENV['GITLAB_ACCESS_TOKEN'] ?? null;
        $this->gitlab_project_id = $_ENV['GITLAB_PROJECT_ID'] ?? null;
        $this->gitlab_labels = $_ENV['GITLAB_LABELS'] ?? null;
    }

    public function execute($options = array())
    {
        $message = $options['message'];
        $client = new \Gitlab\Client();
        $client->setUrl($this->gitlab_url);
        $client->authenticate($this->gitlab_access_token, \Gitlab\Client::AUTH_HTTP_TOKEN);
        $body = "Reporting user: " . (string)$options['user'];
        $body .= "\nReport:\n\n" . $message->getBody();
        
        $gitlab_options = [
            'description' => $body,
            'title' => $message->getSubject(),
            'issue_type' => 'issue',
            'labels' => $this->gitlab_labels
        ];
        $client->issues()->create($this->gitlab_project_id, $gitlab_options);
    }
}
