<?php
class payload_manager 
{
    var $payload;

    function payload_manager( $session ) 
    {
        $this->payload = $session;
    }

    function add( $key, $val ) 
    {
        $this->payload['queue']['payload'][$key] = $val;
    }

    function get( $key = null ) 
    {
        if ( $key == null ) 
        {
            return $this->payload['queue']['payload'];
        } 
        else 
        {
            return $this->payload['queue']['payload'][$key];
        }
    }

    function get_dataset( $index = 0 )
    {
        $dataset = $this->payload['queue']['payload'];

        // Save only one dataset
        $temp    = array();
        $temp    = $dataset['dataset'][$index];
        $dataset['dataset'] = array();
        $dataset['dataset'][0] = $temp;

        // Change the count to reflect a single dataset
        $dataset['datasetCount'] = 1;

        return $dataset;
    }

    function remove( $key ) 
    {
        unset($this->payload['queue']['payload'][$key]);
    }

    function clear() 
    {
        unset( $this->payload['queue']['payload'] );
        unset( $_SESSION['payload_mgr'] );
    }

    function save()
    {
        unset( $_SESSION['payload_mgr'] );

        foreach ( $this->payload['queue']['payload'] as $key => $value )
          $_SESSION['payload_mgr'][$key] = $value;
    }

    function restore()
    {
        if ( isset($_SESSION['payload_mgr']) )
        {
            foreach( $_SESSION['payload_mgr'] as $key => $value )
              $this->add( $key, $value );
        }

        unset( $_SESSION['payload_mgr'] );
    }
}
?>
