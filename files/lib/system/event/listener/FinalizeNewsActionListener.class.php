<?php
namespace cms\system\event\listener;
use wbb\data\board\BoardCache;
use wbb\data\thread\ThreadAction;
use wcf\system\event\IEventListener;
use wcf\system\tagging\TagEngine;

/**
 * @author	Jens Krumsieck
 * @copyright	2014 codeQuake
 * @license	GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl-3.0.txt>
 * @package	de.codequake.cms
 */

class FinalizeNewsActionListener implements IEventListener{

	public $eventObj = null;

	public function execute($eventObj, $className, $eventName) {
		$this->eventObj = $eventObj;
		if (method_exists($this, $eventObj->getActionName())) {
			$this->{$eventObj->getActionName()}();
		}
	}

	public function create() {
		$board = BoardCache::getInstance()->getBoard(CMS_NEWS_POST_BOARD);
		if ($board === null || !$board->isBoard()) return;
		$returnValues = $this->eventObj->getReturnValues();
		$news = $returnValues['returnValues'];
		$data = array(
			'boardID' => $board->boardID,
			'languageID' => $news->languageID,
			'topic' => $news->subject,
			'time' => $news->time,
			'userID' => $news->userID,
			'username' => $news->username,
			'hasLabels' => 0
		);
		$threadData = array(
			'board' => $board,
			'data' => $data,
			'postData' => array(
				'message' => ($news->teaser != '' ? $news->teaser : $news->message).' [news='.$news->newsID.'][/news]',
				'enableBBCodes' => 1,
				'enableHtml' => 0,
				'enableSmilies' => $news->enableSmilies,
				'showSignature' => $news->showSignature
			),
			'tags' => array()
			);
			//enable tags
			if (MODULE_TAGGING && WBB_THREAD_ENABLE_TAGS) {
				$tags = TagEngine::getInstance()->getObjectTags('de.codequake.cms.news', $news->newsID);
				foreach ($tags as $tag) {
					$threadData['tags'][] = $tag->name;
				}
			}
			$action = new ThreadAction(array(), 'create', $threadData);
			$resultValues = $action->executeAction();

			//mark as read
			$action = new ThreadAction(array($resultValues['returnValues']), 'markAsRead');
			$action->executeAction();
	}
}
