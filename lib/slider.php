<?php 
class slider {
	var $name;
	var $min;
	var $max;
	var $default;
	var $display;

	/* Initializer */
	function slider( $name, $min, $max, $default, $display ) {
		$this->name = $name;
		$this->min = $min;
		$this->max = $max;
		$this->default = $default;	
		$this->display = $display;
	}

	/* Access functions */
	function getDisplay() {
		$dis = "<fieldset>";
                $dis .= "	<legend>".$this->display."</legend>";
                $dis .= "	<div class=\"slider\" id=\"".$this->name."-slider\" tabIndex=\"1\">";
                $dis .= "		<input class=\"slider-input\" id=\"".$this->name."-slider-input\"/>";
                $dis .= "        </div>";
                $dis .= "        <br/>";
                $dis .= "        Value: <input id=\"".$this->name."-value\" onchange=\"".$this->name.".setValue(parseInt(this.value))\" name=\"".$this->name."-value\" value=\"".$this->name.".setValue(parseInt(this.value))\"/>";
                $dis .= "        Minimum: <input id=\"".$this->name."-min\" onchange=\"".$this->name.".setMinimum(parseInt(this.value))\" disabled=\"disabled\"/>";
                $dis .= "        Maximum: <input id=\"".$this->name."-max\" onchange=\"".$this->name.".setMaximum(parseInt(this.value))\" disabled=\"disabled\"/>";
              	$dis .= "</fieldset>";
		return $dis;
	}

	function getJavaScript() {
		$js = "var ".$this->name." = new Slider(document.getElementById(\"".$this->name."-slider\"), document.getElementById(\"".$this->name."-slider-input\"));";
		$js .= $this->name.".setMinimum(".$this->min.");";
		$js .= $this->name.".setMaximum(".$this->max.");";
		$js .= $this->name.".setValue(".$this->default.");";
		$js .= "document.getElementById(\"".$this->name."-value\").value = ".$this->name.".getValue();";
		$js .= "document.getElementById(\"".$this->name."-min\").value = ".$this->name.".getMinimum();";
		$js .= "document.getElementById(\"".$this->name."-max\").value = ".$this->name.".getMaximum();";
		$js .= $this->name.".onchange = function () {";
		$js .= "        document.getElementById(\"".$this->name."-value\").value = ".$this->name.".getValue();";
		$js .= "        document.getElementById(\"".$this->name."-min\").value = ".$this->name.".getMinimum();";
		$js .= "        document.getElementById(\"".$this->name."-max\").value = ".$this->name.".getMaximum();";
		$js .= "};";
		return $js; 
	}	

	/* Set functions */
	function setName( $name ) {
		$this->name = $name;
	}

	function setMin( $min ) {
		$this->min = $min;
	}

	function setMax( $max ) {
		$this->max = $max;
	}

	function setDefault( $default ) {
		$this->default = $default;
	}

	function setDisplay( $display ) {
		$this->display = $display;
	}
}
?>
