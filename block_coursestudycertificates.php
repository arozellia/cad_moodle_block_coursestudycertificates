<?php
declare(strict_types=1);

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block coursestudycertificates is defined here.
 *
 * @package     block_coursestudycertificates
 * @copyright   2019 Tia <tia@techiasolutions.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * coursestudycertificates block.
 *
 * @package    block_coursestudycertificates
 * @copyright  2019 Tia <tia@techiasolutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursestudycertificates extends \block_base
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $content;

    /**
     * Initializes class member variables.
     * @throws coding_exception
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_coursestudycertificates');
    }
    
	/**
	 * Returns the certificate results for mod/certificate.
	 *
	 * @return array Certification data for the user.
	 * @throws \moodle_exception
	 */
    private function get_certificate_data() : array{
    	global $USER, $DB;
    	
    	$sql = "SELECT DISTINCT UUID() as rand, issues.id, 'cert' AS certificationtype
              		, cm.id AS certificationid
					, cert.name
					, c.fullname
				FROM {certificate} AS cert
					INNER JOIN {certificate_issues} AS  issues on issues.certificateid = cert.id
				    INNER JOIN {course_modules} AS  cm on cm.instance = cert.id  and cm.course = cert.course
				    INNER JOIN {modules} AS  m on m.id = cm.module and m.name = 'certificate'
					INNER JOIN {course} c on c.id = cm.course
			    WHERE issues.userid = ?
			    UNION
			    SELECT DISTINCT  UUID() as rand, issues.id, 'customcert' AS certificationtype
              		, issues.id AS certificationid
					, cert.name
					, c.fullname
				FROM {customcert} AS cert
					INNER JOIN {customcert_issues} AS  issues on issues.customcertid = cert.id
				    INNER JOIN {course_modules} AS  cm on cm.instance = cert.id  and cm.course = cert.course
				    INNER JOIN {modules} AS  m on m.id = cm.module and m.name = 'certificate'
					INNER JOIN {course} c on c.id = cm.course
			    WHERE issues.userid = ?";
		$result = $DB->get_records_sql($sql, [$USER->id, $USER->id]);

		return $result;
    }
    
    /**
     * Returns the block contents.
     *
     * @return stdClass|String The block contents.
     * @throws \moodle_exception
     */
    public function get_content()
    {
    	global $USER;
    	
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
	        $certificationData = $this->get_certificate_data();
	        $customCertPath = [];
	        $customCertPath['cert'] = '/mod/certificate/view.php';
	        $customCertPath['customcert'] = '/mod/customcert/my_certificates.php';
	        $text = '';
	        
	        if (empty($certificationData)) {
		        $this->content->text = '<div class="alert alert-light" role="alert">No certificates found.</div>';
	        } else {
	        	array_walk($certificationData, function($certification) use (&$text, $USER, $customCertPath) {
			        $text .= '<a role="button" href="';
			        $text .= new moodle_url($customCertPath[$certification->certificationtype], [
			        	'id' => $certification->certificationid,
			        	'certificateid' => $certification->certificationid,
			        	'userid' => $USER->id,
			        	'downloadcert' => 1
			        ]); 
			        $text .= '"class="btn btn-labeled btn-primary btn-sm btn-block text-left active">';
	        		$text .= '<span style="font-family: FontAwesome;">&#xf0a3;</span> ';
	        		$text .= $certification->fullname . ': ' . $certification->name;
	        		$text .= '</a>';
		        });
	        }

	        $this->content->text = $text;
        }

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     * @throws coding_exception
     */
    public function specialization()
    {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('usertitle', 'block_coursestudycertificates');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Allow multiple instances in a single course?
     *
     * @return bool True if multiple instances are allowed, false otherwise.
     */
    public function instance_allow_multiple()
    {
        return true;
    }

    function _self_test()
    {
        return true;
    }

    function applicable_formats()
    {
        return array(
            'all' => true,
            'mod' => true,
        );
    }
}
