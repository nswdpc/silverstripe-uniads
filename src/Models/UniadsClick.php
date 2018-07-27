<?php
namespace SilverstripeUniads\Model;

/**
 * Description of UniadsClick
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @author James Ellis <james.ellis@dpc.nsw.gov.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsClick extends UniadsImpression {
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UniadsClick';

    private static $singular_name = 'Click';
    private static $plural_name = 'Clicks';
}
