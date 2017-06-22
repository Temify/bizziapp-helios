<?php
namespace HeliosAPI\Types;

class ZboziType
{
    public $ID = 0;
    public $Nazev1 = '';
    public $Nazev2 = '';
    public $RegCis = '';
    public $BaleniTXT = '';

    public function __construct (Array $data)
    {
        foreach($data as $Key => $Value)
        {
            if(property_exists($this, $Key))
                $this->$Key = $Value;
        }
    }

}
?>