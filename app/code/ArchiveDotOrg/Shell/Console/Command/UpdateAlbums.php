<?php
//Comleted:
// setup ec2 with code and hosting
// setup rds
// Custom Product Attribute (25)
// Custom Category Attribute (1)
// Custpm Category Admin Tab (1)
// Custo SKU length, from 64 to 255
// Custom Shell Command to import products
// create new dropdown attribute values on the fly
// Import product image from 3rd party URL


//ToDo:
// add reviews
// add to artist category
// add to album and track categories
// add project to github by module properly
// proper security & permissions & user setup


namespace ArchiveDotOrg\Shell\Console\Command;

// https://www.thirdandgrove.com/create-magento-module-command-import-products
// https://stackoverflow.com/questions/34740555/how-to-get-model-and-product-collection-in-magento-2
// https://www.thirdandgrove.com/create-magento-module-command-import-products

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use ArchiveDotOrg\Shell\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\{ObjectManager, State};
use Magento\Catalog\Model\ProductRepository;


class UpdateAlbums extends Command
{
	
	/**
	* @var \VendorName\ExtensionName\Service\ImportImageService
	*/
	protected $importimageservice;
	
	 /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $attributeValues;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\Source\TableFactory
     */
    protected $tableFactory;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $attributeOptionManagement;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory
     */
    protected $optionLabelFactory;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory
     */
    protected $optionFactory;
	/**
	* Constructor
	*
	* @param State $state A Magento app State instance
	*
	* @return void
	*/
	public function __construct(
		State $state, 
		ProductRepositoryInterface $prepo,
		\Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Eav\Model\Entity\Attribute\Source\TableFactory $tableFactory,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory,
        \ArchiveDotOrg\Shell\Service\ImportImageService $importimageservice
	)
	{
		// We cannot use core functions (like saving a product) unless the area
		// code is explicitly set.
		try {
			$state->setAreaCode('adminhtml');
		} catch (\Magento\Framework\Exception\LocalizedException $e) {
			// Intentionally left empty.
		}
		parent::__construct();
		$this->importimageservice = $importimageservice;
        $this->attributeRepository = $attributeRepository;
        $this->tableFactory = $tableFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;		
	}
	
	
	
   protected function configure()
   {
       $this->setName('ArchiveDotOrg:GetProducts');
       $this->setDescription('Demo command line');
       
       parent::configure();
   }
   

   protected function execute(InputInterface $input, OutputInterface $output)
   {
   	
	try{
	    $output->writeln("<info>Starting...</info>");
	    $parent_category_id = 2; //Default / Root category ID
	   	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$categoryObj = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->load($parent_category_id);
		$subcategories = $categoryObj->getChildrenCategories();
		$__count_cats = 0;
		$__count_shows = 0;
		$__count_songs = 0;
		foreach($subcategories as $subcategorie) {
			$__count_cats = $__count_cats + 1;
			$_subcategory_info = array();
			$_subcategory_info['name'] = $subcategorie->getData('name');
			$subcategorie = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create()->load($subcategorie->getId());
		    $output->writeln('<info>     On Artist #'.$__count_cats.' Reading RSS --> '.$subcategorie->getData('artist_collection_rss').'</info>');
			$collection_feed = file_get_contents($subcategorie->getData('artist_collection_rss'));
			$collection_rss = simplexml_load_string($collection_feed);				
			foreach($collection_rss->channel->item as $_item){
				$__count_shows = $__count_shows + 1;
				$_show_vars = array();	
				$_show_vars['title'] = $_item->title;
				$_show_vars['description'] = $_item->description;
				$_show_vars['pubDate'] = $_item->pubDate;
				$_show_vars['category'] = $_item->category;
				$_show_vars['guid'] = $_item->guid;
				$_show_vars['guid_clone'] = $_item->guid;									
				$_show_vars['feed'] = file_get_contents(str_replace('/details/','/metadata/',$_show_vars['guid']));
				$_show_vars['feed_url'] = str_replace('/details/','/metadata/',$_show_vars['guid']);
				$_show_vars['json'] = json_decode($_show_vars['feed']);
				$_show_vars['server_one'] = $_show_vars['json']->d1;
				$_show_vars['server_two'] = $_show_vars['json']->d2;
				$_show_vars['dir'] = $_show_vars['json']->dir;
				$_show_vars['year'] = $_show_vars['json']->metadata->year;
				if(isset($_show_vars['json']->metadata->venue) && trim($_show_vars['json']->metadata->venue) != ''){$_show_vars['venue'] = $_show_vars['json']->metadata->venue;}else{$_show_vars['venue'] = 'not stored';}
				if(isset($_show_vars['json']->metadata->notes) && trim($_show_vars['json']->metadata->notes) != ''){$_show_vars['notes'] = $_show_vars['json']->metadata->notes;}else{$_show_vars['notes'] = 'not stored';}
				if(isset($_show_vars['json']->metadata->taper) && trim($_show_vars['json']->metadata->taper) != ''){$_show_vars['taper'] = $_show_vars['json']->metadata->taper;}else{$_show_vars['taper'] = 'not stored';}
				$_show_vars['collection'] = $_show_vars['json']->metadata->collection;				
				if(isset($_show_vars['json']->metadata->transferer) && trim($_show_vars['json']->metadata->transferer) != ''){$_show_vars['transferer'] = $_show_vars['json']->metadata->transferer;}else{$_show_vars['transferer'] = 'not stored';}
				//$_show_vars['location']  = $_show_vars['json']->metadata->location;
				$_show_vars['title'] = $_show_vars['json']->metadata->title;
				if(isset($_show_vars['json']->metadata->lineage) && trim($_show_vars['json']->metadata->lineage) != ''){$_show_vars['lineage'] = $_show_vars['json']->metadata->lineage;}else{$_show_vars['lineage'] = 'not stored';}			
				$_show_vars['identifier'] = $_show_vars['json']->metadata->identifier;
				$output->writeln('<info>          On Show #'.$__count_shows.'    --> '.$_show_vars['title'].' | '. $_show_vars['feed_url'].'</info>');
				$output->writeln('<info>               venue::'.$_show_vars['venue'].' || taper::'.$_show_vars['taper'].' || transferer::'.$_show_vars['transferer'].' || year::'.$_show_vars['year'].'</info>');
				foreach($_show_vars['json']->files as $_song){					
					$_song_var = array();						
					if($this->endsWith($_song->name, '.flac') && isset($_song->title)){
						$__count_songs = $__count_songs + 1;
						$_song_var['sku'] = substr($_song->name, 0, -5);
						$_song_var['length'] = $_song->length;	
						$_song_var['title'] = $_song->title;	
						$_song_var['name'] = $_subcategory_info['name'].' '.$_song_var['title'].' '.$_show_vars['year'].' '.$_show_vars['venue'];													
						$productMod = $objectManager->get('Magento\Catalog\Model\Product');
						$_update_flag = 0;												
						if($productMod->getIdBySku($_song_var['sku'])){
						    $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productMod->getIdBySku($_song_var['sku']));
							$output->writeln('<comment>               On Song #'.$__count_songs.' Updating --> sku::'.$_song_var['sku'].' || title::'.$_song_var['title'].' || name::'.$_song_var['name'].' || source::'.$_song->source.'</comment>');
							$_update_flag = 1;
						} else {							
						    $product = $objectManager->create('Magento\Catalog\Model\Product');
							$output->writeln('<info>               On Song #'.$__count_songs.' Adding --> sku::'.$_song_var['sku'].' || title::'.$_song_var['title'].' || name::'.$_song_var['name'].' || source::'.$_song->source.'</info>');									
						}						
						$product->setDescription($_show_vars['description']);
						$product->setName($_song_var['name']);
						$product->setSku($_song_var['sku']);
						//$datetime = new \DateTime($_show_pubDate, new \DateTimeZone('GMT'));
						//$datetime->setTimezone(new \DateTimeZone('America/New_York'));
						//$_show_pubDate = $datetime->format('Y-m-d H:i:s (e)');
						$product->setPubDate($_show_vars['pubDate']);
						$product->setGuid($_show_vars['guid']);
						$product->setServerOne($_show_vars['server_one']);
						$product->setServerTwo($_show_vars['server_two']);
						$atr_year = $this->createOrGetId('year', $_show_vars['year']);
						$product->setYear($atr_year);
						$atr_venue = $this->createOrGetId('venue', $_show_vars['venue']);
						$product->setVenue($atr_venue);											
						$atr_taper = $this->createOrGetId('taper', $_show_vars['taper']);
						$product->setTaper($atr_taper);						
						$atr_transfer = $this->createOrGetId('transfer', $_show_vars['transferer']);
						$product->setTransfer($atr_transfer);
						$product->setTitle($_song_var['title']);						
						$product->setArchiveCollection($_subcategory_info['name']);
						$product->setShowName($_show_vars['title']);
						$product->setLength($_song_var['length']);
						$product->setCollection($_show_vars['collection']);	
						$product->setNotes($_show_vars['notes']);						
						if(isset($_show_vars['json']->metadata->lineage)){$product->setLineage($_show_vars['lineage']);}
						$product->setUrlKey(strtolower(preg_replace('#[^0-9a-z]+#i', '-', $_song_var['title'])).'_'.strtolower(preg_replace('#[^0-9a-z]+#i', '-', $_song_var['sku'])));
						$product->setIdentifier($_show_vars['identifier']);
						$product->setDir($_show_vars['dir']);
						$product->setSongUrl($_show_vars['server_one'].'/'.$_show_vars['dir'].'/'.$_song_var['sku'].'.flac');							
						//
						$product->setPrice(0);
						$product->setTypeId('virtual');
						$product->setWebsiteIds([1]);
						$product->setStoreId(1);
						$product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
						$product->setAttributeSetId(4);
						$product->setVisibility(4);
						$product->setStockData(array(
				            'use_config_manage_stock' => 0, //'Use config settings' checkbox
				            'manage_stock' => 0, //manage stock
				            'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
				            'max_sale_qty' => 2, //Maximum Qty Allowed in Shopping Cart
				            'is_in_stock' => 1, //Stock Availability
				            'qty' => 100000 //qty
				            )
				        );													
						//$output->writeln('<info>               Saving...</info>');						
						if(!$product->getData('image')){
							$_imagePath = 'https://'.$_show_vars['server_one'].$_show_vars['dir'].'/'.$_song_var['sku'].'.png'; // path of the image
							$_imagePath2 = 'https://'.$_show_vars['server_one'].$_show_vars['dir'].'/'.$_song_var['sku'].'_spectrogram.png'; // path of the image						
							$this->importimageservice->execute($product, $_imagePath, $__visible = true, $__imageType = ['image', 'small_image', 'thumbnail']);
							$this->importimageservice->execute($product, $_imagePath2, $__visible = true);
							$output->writeln('<info>               Adding Image</info>');
							
						}	
						if($_update_flag == 0){														
							$product->save();
						}
						//$output->writeln('<info>               .........OK</info>');
						unset($product);						
					}
				}
				
			}

		}
	   $output->writeln("<info>Done.</info>");
	   }catch (exception $e){
	   		$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
	   		print_r($product);
	   		$output->writeln('<error>'.$e->getMessage().'</error>');
			$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
	   }
    }

	
	protected function startsWith($haystack, $needle)
	{
	     $length = strlen($needle);
	     return (substr($haystack, 0, $length) === $needle);
	}
	
	protected function endsWith($haystack, $needle)
	{
	    $length = strlen($needle);
	    if ($length == 0) {
	        return true;
	    }
	
	    return (substr($haystack, -$length) === $needle);
	}


 	 /**
     * Get attribute by code.
     *
     * @param string $attributeCode
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function getAttribute($attributeCode)
    {
        return $this->attributeRepository->get($attributeCode);
    }


    /**
     * Find or create a matching attribute option
     *
     * @param string $attributeCode Attribute the option should exist in
     * @param string $label Label to find or add
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
 	public function createOrGetId($attributeCode, $label)
    {
		try{
	        if (strlen($label) < 1) {
	            throw new \Magento\Framework\Exception\LocalizedException(
	                __('Label for %1 must not be empty.', $attributeCode)
	            );
	        }
	        // Does it already exist?
	        $optionId = $this->getOptionId($attributeCode, $label);
	        if (!$optionId) {
	            // If no, add it.	
	            /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $optionLabel */
	            $optionLabel = $this->optionLabelFactory->create();
	            $optionLabel->setStoreId(1);
	            $optionLabel->setLabel($label);
	            $option = $this->optionFactory->create();
	            $option->setLabel($label);
	            $option->setStoreLabels([$optionLabel]);
	            $option->setSortOrder(0);
	            $option->setIsDefault(false);
	            $this->attributeOptionManagement->add(
	                \Magento\Catalog\Model\Product::ENTITY,
	                $this->getAttribute($attributeCode)->getAttributeId(),
	                $option
	            );
	            // Get the inserted ID. Should be returned from the installer, but it isn't.
	            $optionId = $this->getOptionId($attributeCode, $label, true);
	        }
	
	        return $optionId;
		}catch(exception $e){
        	$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
//	   		print_r($product);
			
	   		$output->writeln('<error> code::'.$attributeCode.' || label::'.$label.'</error>');
	   		$output->writeln('<error>'.$e->getMessage().'</error>');
			$output->writeln('<error>-----------------------------</error>');
			$output->writeln('<error>-----------------------------</error>');
		}
    }


    /**
     * Find the ID of an option matching $label, if any.
     *
     * @param string $attributeCode Attribute code
     * @param string $label Label to find
     * @param bool $force If true, will fetch the options even if they're already cached.
     * @return int|false
     */
    public function getOptionId($attributeCode, $label, $force = false)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->getAttribute($attributeCode);

        // Build option array if necessary
        if ($force === true || !isset($this->attributeValues[ $attribute->getAttributeId() ])) {
            $this->attributeValues[ $attribute->getAttributeId() ] = [];

            // We have to generate a new sourceModel instance each time through to prevent it from
            // referencing its _options cache. No other way to get it to pick up newly-added values.

            /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceModel */
            $sourceModel = $this->tableFactory->create();
            $sourceModel->setAttribute($attribute);

            foreach ($sourceModel->getAllOptions() as $option) {
                $this->attributeValues[ $attribute->getAttributeId() ][ $option['label'] ] = $option['value'];
            }
        }

        // Return option ID if exists
        if (isset($this->attributeValues[ $attribute->getAttributeId() ][ $label ])) {
            return $this->attributeValues[ $attribute->getAttributeId() ][ $label ];
        }

        // Return false if does not exist
        return false;
    }




}
