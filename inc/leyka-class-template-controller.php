<?php if( !defined('WPINC') ) die;
/**
 * Leyka Templates Controller class.
 **/

abstract class Leyka_Template_Controller extends Leyka_Singleton {

    protected static $_instance;

    protected $_global_template_data = array();
    protected $_template_data = array();
    protected $_campaigns = array();

    public function __get($name) {
        return empty($this->_global_template_data[$name]) ? null : $this->_global_template_data[$name];
    }

    public function __set($name, $value) {
        $this->_global_template_data[$name] = $value;
    }

    /** A wrapper for templates to get current campaign object without direct using of global vars. */
    public function getCurrentCampaign() {

        if($this->current_campaign) {

            if(empty($this->_campaigns[$this->current_campaign->id]) && is_a($this->current_campaign, 'Leyka_Campaign')) {
                $this->_campaigns[$this->current_campaign->id] = $this->current_campaign;
            }

            return $this->current_campaign;

        }

        $campaign = get_post();
        if( !$campaign ) {
            return false;
        }

        $campaign = leyka_get_validated_campaign($campaign);
        if($campaign && empty($this->_campaigns[$campaign->id])) {
            $this->_campaigns[$campaign->id] = $campaign;
        }

        return $campaign;

    }

    /**
     * Get an array of template data for the campaign given + cache the data for further use.
     * @param $campaign mixed
     * @return array
     */
    public function getTemplateData($campaign = false) {

        if( !$campaign ) {
            $campaign = $this->getCurrentCampaign();
        }

        if( !$campaign ) {
            return array(); /** @todo There is no such campaign. Mb, throw some exception here. */
        }

        if(empty($this->_campaigns[$campaign->id])) {
            $this->_campaigns[$campaign->id] = $campaign;
        }
        if(empty($this->_template_data[$campaign->id])) {
            $this->_generateTemplateData($campaign);
        }

        return empty($this->_template_data[$campaign->id]) ? array() : $this->_template_data[$campaign->id];

    }
    
    /**
     * Get all template data from DB, do some calculations, create a array.
     * @param $campaign Leyka_Campaign
     * @return null
     */
    abstract protected function _generateTemplateData(Leyka_Campaign $campaign);

}