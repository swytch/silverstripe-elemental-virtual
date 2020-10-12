<?php

namespace DNADesign\ElementalVirtual\Model;

use TractorCow\AutoComplete\AutoCompleteField;
use SilverStripe\ElementalVirtual\Forms\ElementalGridFieldAddExistingAutocompleter;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Virtual Linked Element.
 *
 * As elemental is based on a natural has_one relation to an object,
 * this allows the same element to be linked to multiple pages.
 *
 * {@see ElementalGridFieldAddExistingAutocompleter}
 */
class ElementVirtual extends BaseElement
{
    private static $icon = 'material-icons font-icon-block-virtual';

    private static $has_one = [
        'LinkedElement' => BaseElement::class
    ];

    /**
     * @var string
     */
    private static $description = 'Reused element';

    private static $table_name = 'ElementVirtual';

    private static $singular_name = 'virtual block';

    /**
     * @param BaseElement
     * @param boolean $isSingleton
     * @param DataModel $model
     */
    public function __construct($record = null, $isSingleton = false, $model = null)
    {
        parent::__construct($record, $isSingleton, $model);

        $this->LinkedElement()->setVirtualOwner($this);
    }

    public function getCMSFields()
    {
        $invalid = $this->isInvalidPublishState();

        $this->beforeUpdateCMSFields(function (FieldList $fields) use ($invalid) {
            $fields->removeByName('Title');

            $message = sprintf(
                '<p>%s</p><p><a href="%2$s" target="_blank">Click here to edit the original</a></p>',
                _t(__CLASS__ . '.VirtualDescription', 'This is a virtual copy of an element.'),
                $this->LinkedElement()->getEditLink()
            );

            if ($invalid) {
                $warning = _t(
                    __CLASS__ . '.InvalidPublishStateWarning',
                    'Error: The original element is not published. This element will not work on the live site until you click the link below and publish it.'
                );

                $fields->addFieldToTab('Root.Main', LiteralField::create('WarningHeader', '<p class="message error">' . $warning . '</p>'));
            }

            $selector = DropdownField::create('LinkedElementID',
                _t(__CLASS__ . '.LinkedElement', 'Linked Element'),
                BaseElement::get()
                    ->filter('AvailableGlobally', true)
                    ->exclude('ClassName', self::class)
                    ->map('ID', 'Title')
                )->setEmptyString('(Select one)');

            $fields->replaceField(
                'LinkedElementID',
                $selector
            );
            $fields->addFieldToTab('Root.Main', LiteralField::create('Existing', $message));
        });

        return parent::getCMSFields();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return sprintf(
            _t(__CLASS__ . '.BlockType', 'Virtual Block')
        );
    }

    /**
     * Detect when a user has published a linked element but has not published
     * the LinkedElement.
     *
     * @return boolean
     */
    public function isInvalidPublishState()
    {
        $element = $this->LinkedElement();

        return (!$element->isPublished() && $this->isPublished());
    }

    /**
     * Get a unique anchor name.
     *
     * @return string
     */
    public function getAnchor()
    {
        $linkedElement = $this->LinkedElement();

        if ($linkedElement && $linkedElement->exists()) {
            return $linkedElement->getAnchor();
        }

        return 'e' . $this->ID;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        if ($linked = $this->LinkedElement()) {
            return $linked->getSummary();
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($linked = $this->LinkedElement()) {
            return $linked->Title;
        }
    }

    /**
     * Override to render template based on LinkedElement
     *
     * @return string|null HTML
     */
    public function forTemplate($holder = true)
    {
        if ($linked = $this->LinkedElement()) {
            return $linked->forTemplate($holder);
        }
        return null;
    }

    /**
     *
     */
    public function inlineEditable()
    {
        return true;
    }

    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->getSummary();
        return $blockSchema;
    }
}
