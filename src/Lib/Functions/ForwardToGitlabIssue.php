<?php

namespace BisonLab\SakonninBundle\Lib\Functions;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use BisonLab\SakonninBundle\Entity\Message;

/*
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

    public function __construct(
        private RouterInterface $router,
        #[Autowire('%env(GITLAB_URL)%')]
        private string $gitlab_url,
        #[Autowire('%env(GITLAB_ACCESS_TOKEN)%')]
        private string $gitlab_access_token,
        #[Autowire('%env(GITLAB_PROJECT_ID)%')]
        private string $gitlab_project_id,
        #[Autowire('%env(GITLAB_LABELS)%')]
        private string $gitlab_labels,
    ) {
    }

    /* 
     * You may call this lazyness, just having an options array, but it's
     * also more future proof.
     */
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
