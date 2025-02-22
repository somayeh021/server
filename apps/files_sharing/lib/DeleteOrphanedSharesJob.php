<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Delete all share entries that have no matching entries in the file cache table.
 */
class DeleteOrphanedSharesJob extends TimedJob {
	/**
	 * sets the correct interval for this timed job
	 */
	public function __construct(ITimeFactory $time) {
		parent::__construct($time);

		$this->setInterval(24 * 60 * 60); // 1 day
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument) {
		$connection = Server::get(IDBConnection::class);
		$logger = Server::get(LoggerInterface::class);

		$sql =
			'DELETE FROM `*PREFIX*share` ' .
			'WHERE `item_type` in (\'file\', \'folder\') ' .
			'AND NOT EXISTS (SELECT `fileid` FROM `*PREFIX*filecache` WHERE `file_source` = `fileid`)';

		$deletedEntries = $connection->executeStatement($sql);
		$logger->debug("$deletedEntries orphaned share(s) deleted", ['app' => 'DeleteOrphanedSharesJob']);
	}
}
