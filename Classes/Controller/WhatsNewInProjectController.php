<?php

namespace Flowpack\Neos\WhatsNewEditor\InMyProject\Controller;

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use DateTimeInterface;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * @Flow\Scope("singleton")
 */
class WhatsNewInProjectController extends ActionController
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {}

    public function indexAction(): string | false
    {
        $siteNode = $this->getSiteNode();
        if ($siteNode === null) {
            return json_encode([
                "clientNotificationTimestamp" => 0,
            ]);
        }

        /** @phpstan-ignore-next-line */
        $node = FlowQuery::q($siteNode)
            ->find('[instanceof Flowpack.Neos.WhatsNewEditor.InMyProject:Document.WhatsNewDashboardPage]')
            ->get(0);

        // No WhatsNewDashboardPage in the site yet -> nullsafe call, no notification.
        $clientNotificationDateTime = $node?->getProperty('clientNotificationDateTime');

        // The content repository returns the property as DateTimeImmutable, so check
        // against the shared interface instead of the mutable DateTime class.
        if ($clientNotificationDateTime instanceof DateTimeInterface) {
            $clientNotificationTimestamp = $clientNotificationDateTime->getTimestamp() * 1000; // to get timestamp in ms instead of seconds to match js timestamp
        } else {
            $clientNotificationTimestamp = 0; // If no DateTime is set, we don't want to show the notification
        }

        return json_encode([
            "clientNotificationTimestamp" => $clientNotificationTimestamp,
        ]);
    }

    private function getSiteNode(): ?Node
    {
        $contentRepositoryId = ContentRepositoryId::fromString('default');
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = WorkspaceName::fromString('live');
        $contentGraph = $contentRepository->getContentGraph($workspaceName);

        // The site node only exists in concrete dimension space points (an empty
        // dimension space point matches nothing on sites with content dimensions),
        // so try all allowed ones — for a dimensionless setup this is just the
        // single empty dimension space point.
        foreach ($contentRepository->getVariationGraph()->getDimensionSpacePoints() as $dimensionSpacePoint) {
            $contentSubgraph = $contentGraph->getSubgraph(
                $dimensionSpacePoint,
                VisibilityConstraints::createEmpty()
            );

            $sitesRootNode = $contentSubgraph->findRootNodeByType(
                NodeTypeName::fromString('Neos.Neos:Sites')
            );

            if ($sitesRootNode === null) {
                continue;
            }

            /** @phpstan-ignore-next-line */
            $siteNode = FlowQuery::q($sitesRootNode)
                ->children('[instanceof Neos.Neos:Site]')
                ->get(0);

            if ($siteNode instanceof Node) {
                return $siteNode;
            }
        }

        return null;
    }
}
