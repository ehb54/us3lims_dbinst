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
            return $this->payload['queue'];
        } 
        else 
        {
            return $this->payload['queue']['payload'][$key];
        }
    }

    function get_dataset( $index = 0 )
    {
        $dataset = $this->payload['queue'];

        // Save only one file
        $temp    = $dataset['payload']['files'][$index];
        $dataset['payload']['files'] = array();
        $dataset['payload']['files'][0] = $temp;

        // Only one setting for simpoints, band_volume, radial_grid
        //  and time_grid
        $temp    = $dataset['payload']['simpoints'][$index];
        $dataset['payload']['simpoints'] = array();
        $dataset['payload']['simpoints'][0] = $temp;

        $temp    = $dataset['payload']['band_volume'][$index];
        $dataset['payload']['band_volume'] = array();
        $dataset['payload']['band_volume'][0] = $temp;

        $temp    = $dataset['payload']['radial_grid'][$index];
        $dataset['payload']['radial_grid'] = array();
        $dataset['payload']['radial_grid'][0] = $temp;

        $temp    = $dataset['payload']['time_grid'][$index];
        $dataset['payload']['time_grid'] = array();
        $dataset['payload']['time_grid'][0] = $temp;

        $dataset['payload']['count'] = 1;

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
