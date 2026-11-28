<?php
namespace USLIMS\Plugin\V1;

/**
 * Plugin API v1 request handler. Declared handlers are constructed without
 * arguments and called only after core has checked the session, the plugin's
 * availability and the action name.
 */
interface Action
{
  /**
   * @param Context $context trusted request state supplied by core
   * @param array   $query   the request's GET parameters, without plugin and action
   */
  public function handle( Context $context, array $query ): void;
}
