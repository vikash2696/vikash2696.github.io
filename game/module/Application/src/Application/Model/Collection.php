<?php
namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Collection
{

    public $id;

    public $c_name;

    public $c_place;
    

    public function exchangeArray($data)
    {
        $this->id = (! empty($data['id'])) ? $data['id'] : null;
        $this->c_name = (! empty($data['c_name'])) ? $data['c_name'] : null;
        $this->c_place = (! empty($data['c_place'])) ? $data['c_place'] : null;
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

}
