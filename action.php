<?php
/**
 * Redirector - Page With Regex in redirectURLs.txt
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     i-net software <tools@inetsoftware.de>
 * @author     Gerry Weissbach <gweissbach@inetsoftware.de>
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
class action_plugin_redirector extends DokuWiki_Action_Plugin {

    var $redirectFileName = 'redirectURLs.txt';
    
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE',  $this, '_redirector');
    }
    
    function _redirector(Doku_Event &$event, $param) {
        global $INFO, $ACT, $conf, $ID;
        
        if ( $INFO['exists'] ){ return; }
        if ( !($ACT == 'notfound' || $ACT == 'show' || substr($ACT,0,7) == 'export_') ) { return; }
        
        if ( file_exists(tpl_incdir() . $this->redirectFileName) ) {
            // Look for the redirect file in template directory
            $this->redirectFileName = tpl_incdir() . $this->redirectFileName;
        } else if ( file_exists(DOKU_INC . 'conf/' . $this->redirectFileName) ) {
            // Look for the redirect file in template directory
            $this->redirectFileName = DOKU_INC . 'conf/' . $this->redirectFileName;
        } else if ( file_exists(dirname(__FILE__) . '/' . $this->redirectFileName) ) {
            // Look for the redirect file in plugin directery
            $this->redirectFileName = dirname(__FILE__) . '/' . $this->redirectFileName;
        } else {
            // Nothing be done
            return;
        }
        
        $redirectURLs = confToHash($this->redirectFileName);
        $checkID = strpos($_SERVER["REQUEST_URI"], '?') === false ? $_SERVER["REQUEST_URI"] : substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], '?'));
        if ( substr($checkID, 0, 1)  != '/' ) $checkID = '/' . $checkID;
        
        $return = preg_replace( array_keys($redirectURLs), array_values($redirectURLs), strtolower($checkID));
        if ( substr($return , -1)  == '/' ) $return  .= $conf['start'];
        
        if ( $return == strtolower($checkID) ) {
            return;
        }
        
        # referer must be set - and its not a bot.
        if ( $this->getConf('doLog') && !empty($_SERVER['HTTP_REFERER']) && !preg_match('/(?i)bot/', $_SERVER['HTTP_USER_AGENT'])) { dbglog("Redirecting: '{$checkID}' to '{$return}'"); }
        
        if ( !empty($_GET) ) {
            unset($_GET['id']);
        
            $params = '';
            foreach( $_GET as $key => $value ) {
                if ( !empty($params) ) {
                    $params .= '&';
                }
                $params .= urlencode($key) . "=" . urlencode($value);
            }
        
            if ( !empty($params) ) {
                $return .= '?' . $params;
            }
        }
        
        if ( $return != $_SERVER['REQUEST_URI'] ) {
            send_redirect($return);
            exit;
        }
    }

}
