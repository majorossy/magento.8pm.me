<?php

namespace ArchiveDotOrg\CategoryWork\Model\Config\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;

/**
* Custom Attribute Renderer
*/
class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource 
{	
	/**
	* @var OptionFactory
	*/
	protected $optionFactory;
	/**
	* @param OptionFactory $optionFactory
	*/
	
	/**
	* Get all options
	*
	* @return array
	*/
	public function getAllOptions()
	{
		/* your Attribute options list*/
		$this->_options=[ ['label'=>'Select Options', 'value'=>''],
			['label'=>'1', 'value'=>'1'],
			['label'=>'2', 'value'=>'2'],
			['label'=>'3', 'value'=>'3'],
			['label'=>'4', 'value'=>'4'],
			['label'=>'5', 'value'=>'5'],
			['label'=>'6', 'value'=>'6'],
			['label'=>'7', 'value'=>'7'],
			['label'=>'8', 'value'=>'8'],
			['label'=>'9', 'value'=>'9']
		];
		return $this->_options;
	}
}