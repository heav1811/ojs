<?php

/**
 * LayoutEditorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor.LayoutEditorAction
 *
 * LayoutEditorAction class.
 *
 * $Id$
 */

class LayoutEditorAction extends Action {
	
	//
	// Actions
	//

	/**
	 * Change the sequence order of a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($articleId, $galleyId, $direction) {
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($articleId);
		}
	}
	
	/**
	 * Delete a galley.
	 * @param $articleId int
	 * @param $galleyId int
	 */
	function deleteGalley($articleId, $galleyId) {
		import('file.ArticleFileManager');
		
		$galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $articleId);
		
		if (isset($galley)) {
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->removePublicFile($galley->getFileName());
			$galleyDao->deleteGalley($galley);
		}
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($articleId, $suppFileId, $direction) {
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
		
		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($articleId);
		}
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $articleId int
	 * @param $suppFileId int
	 */
	function deleteSuppFile($articleId, $suppFileId) {
		import('file.ArticleFileManager');
		
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $articleId);
		if (isset($suppFile)) {
			$articleFileManager = &new ArticleFileManager($articleId);
			$articleFileManager->removeSuppFile($suppFile->getFileName());
			$suppFileDao->deleteSuppFile($suppFile);
		}
	}
	
	/**
	 * Marks layout assignment as completed.
	 * @param $articleId int
	 * @param $send boolean
	 */
	function completeLayoutEditing($articleId, $send = false) {
		$submissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$email = &new ArticleMailTemplate($articleId, 'LAYOUT_COMPLETE');
		$submission = &$submissionDao->getSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();
		$editAssignment = &$submission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send) {
			$email->addRecipient($editor->getEmail(), $editor->getFullName());
			$email->setFrom($user->getEmail(), $user->getFullName());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_COMPLETE, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
			$email->send();
				
			$layoutAssignment->setDateCompleted(Core::getCurrentDate());
			$submissionDao->updateSubmission($submission);
			
		} else {
			$paramArray = array(
				'editorialContactName' => $editor->getFullName(),
				'journalName' => $journal->getSetting('journalTitle'),
				'articleTitle' => $copyeditorSubmission->getArticleTitle(),
				'layoutEditorName' => $user->getFullName()
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/copyeditor/completeCopyedit/send', array('articleId' => $articleId));
		}
	}
	
}

?>
