<?php
namespace USLIMS\Plugin\Host;

use USLIMS\Plugin\V1\Context;

/** Core's Context, built from server-side state only. */
final class RequestContext implements Context
{
  /** @var string */
  private $pluginId;
  /** @var string */
  private $instanceId;
  /** @var int */
  private $loginId;
  /** @var \mysqli */
  private $db;
  /** @var array{title:string,html:string}|null */
  private $page;

  public function __construct( string $pluginId, string $instanceId, int $loginId, \mysqli $db )
  {
    $this->pluginId   = $pluginId;
    $this->instanceId = $instanceId;
    $this->loginId    = $loginId;
    $this->db         = $db;
  }

  public function instanceId(): string
  {
    return $this->instanceId;
  }

  public function loginId(): int
  {
    return $this->loginId;
  }

  public function db(): \mysqli
  {
    return $this->db;
  }

  public function actionUrl( string $action, array $params = array() ): string
  {
    return 'plugin.php?' . http_build_query( array( 'plugin' => $this->pluginId, 'action' => $action ) + $params, '', '&' );
  }

  public function renderPage( string $title, string $contentHtml ): void
  {
    $this->page = array( 'title' => $title, 'html' => $contentHtml );
  }

  /** @return array{title:string,html:string}|null */
  public function page(): ?array
  {
    return $this->page;
  }
}
