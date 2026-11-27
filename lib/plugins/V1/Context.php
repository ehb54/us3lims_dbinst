<?php
namespace USLIMS\Plugin\V1;

/**
 * Trusted request state core passes to a plugin action.
 */
interface Context
{
  /** Stable per-instance ID from the instance's plugin settings. */
  public function instanceId(): string;

  /** The signed-in person's actual loginID; never a delegated investigator. */
  public function loginId(): int;

  /** The instance's existing database connection. */
  public function db(): \mysqli;

  /** Relative URL of another action of the same plugin. */
  public function actionUrl( string $action, array $params = array() ): string;

  /**
   * Show HTML content inside the LIMS page layout once the handler returns.
   * Set the status code with http_response_code() first if it is not 200.
   */
  public function renderPage( string $title, string $contentHtml ): void;
}
