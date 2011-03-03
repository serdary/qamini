<?php
/**
 * Qamini Post Status Enums
 *
 * @package   qamini
 * @since     0.1.0
 * @author    Serdar Yildirim
 */
class Helper_PostStatus {        
	const PUBLISHED = 'published';
	const ACCEPTED = 'accepted';
	const CLOSED = 'closed';
	
	// Below constants do not have DB enum 
	const ALL = 'all';
	const ANSWERED = 'answered';
	const UNANSWERED = 'unanswered';
}