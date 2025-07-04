<?php
namespace Flowpack\Neos\WhatsNewEditor\InMyProject\Controller;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Neos\Domain\Service\ContentContext;
use DateTime;

/**
 * @Flow\Scope("singleton")
 */
class WhatsNewInProjectController extends ActionController
{
    #[Flow\Inject]
    protected ContextFactoryInterface $contextFactory;

    /**
     * @return string
     */
    public function indexAction()
    {
        $rootNode = $this->getSiteNode();
        $flowQuery = new FlowQuery([$rootNode]);
        $node = $flowQuery->find('[instanceof Flowpack.Neos.WhatsNewEditor.InMyProject:Document.WhatsNewDashboardPage]')->get(0);
        $clientNotificationDateTime = $node->getProperty('clientNotificationDateTime');
        if (!$clientNotificationDateTime instanceof DateTime) {
            $clientNotificationDateTime = new DateTime('now');
        }

        return json_encode([
            "clientNotificationTimestamp" => $clientNotificationDateTime->getTimestamp() * 1000 // to get timestamp in ms instead of seconds to match js timestamp
        ]);
    }

    /**
     * @return NodeInterface
     */
    private function getSiteNode(): NodeInterface
    {
        /** @var ContentContext $context */
        $context = $this->contextFactory->create([
            'workspaceName' => 'live',
            'invisibleContentShown' => true,
            'dimensions' => [
            ],
            'targetDimensions' => [
            ]

        ]);
        return $context->getCurrentSiteNode();
    }
}
