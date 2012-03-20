<?php
/**
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Pike\EntityAudit;

/**
 * Revision is returned from {@link AuditReader::getRevisions()}
 */
class Revision
{
    /**
     * @var string
     */
    private $rev;

    /**
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @var string
     */
    private $username;

    /**
     * Constructor
     *
     * @param string    $rev
     * @param \DateTime $timestamp
     * @param string    $username
     */
    function __construct($rev, $timestamp, $username)
    {
        $this->rev = $rev;
        $this->timestamp = $timestamp;
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getRev()
    {
        return $this->rev;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
}