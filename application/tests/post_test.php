<?php
/**
 * Model_Post actions test
 * 
 * @group anonymous
 * @group invalid
 */
class PostTest extends PHPUnit_Framework_TestCase
{
	private static $_empty_db = FALSE;
	
    protected function setUp()
    {
        Kohana::config('database')->default = Kohana::config('database')->test;
        
        if (!self::$_empty_db)
        	$this->prepare_db();

    	Auth::instance()->login('unittest', '12345');
    }

    /* QUESTION TEST METHODS */
    
    /*
     * Add 10 anonymous questions with ids 1..10
     */
    public function testAddQuestionGuest()
    {
    	for ($i = 1; $i < 11; $i++)
	   		$this->addQuestionGuest($i);
    }

    /**
     * @depends testAddQuestionGuest
     */
    /*
     * Add 10 user questions with ids 10..20
     */
    public function testAddQuestionUser()
    {
    	for ($i = 1; $i < 11; $i++)
    		$this->addQuestionUser($i);
    }

    /**
     * @depends testAddQuestionUser
     */    
    /*
     * error on purpose => id:3 and id:5, update id:13, id:14 
     */
    public function testUpdateUserQuestion()
    {
    	$this->updateUserQuestion(3, FALSE);
    	$this->updateUserQuestion(5, FALSE);
    	$this->updateUserQuestion(13);
    	$this->updateUserQuestion(14);
    }
    
    /**
     * @depends testUpdateUserQuestion
     */  
    /*
     * error on purpose => id:2, delete id:18, id:19 
     */   
    public function testDeleteUserQuestion()
    {
    	$this->deleteUserQuestion(2, FALSE);
    	$this->deleteUserQuestion(18);
    	$this->deleteUserQuestion(19);
    }
    
    /* ANSWER TEST METHODS */
    
    /**
     * @depends testDeleteUserQuestion
     */     
    /*
     * Add 4 anonymous Answers with ids 21 (parent:1), 22(P:2), 23(P:3), 24(P:1), 25(P:12)
     */
    public function testAddAnswerGuest()
    {
        for ($i = 0; $i < 4; $i++)
    		$this->addAnswerGuest(($i % 3) + 1);
    		
    	$this->addAnswerGuest(12);
    }

    /**
     * @depends testAddAnswerGuest
     */      
    /*
     * Add 4 user Answers with ids 26 (P:1), 27(P:4), 28(P:9), 29(P:16)
     */ 
    public function testAddAnswerUser()
    {
    	for ($i = 1; $i < 5; $i++)
    		$this->addAnswerUser($i * $i);
    }

    /**
     * @depends testAddAnswerUser
     */     
    /*
     * error on purpose => id:21(P:2), id:22(P:1) , update id:25(P:1), id:27(P:9) 
     */   
    public function testUpdateUserAnswer()
    {
    	$this->updateUserAnswer(21, 2, FALSE);
    	$this->updateUserAnswer(22, 1, FALSE);
    	$this->updateUserAnswer(26, 1);
    	$this->updateUserAnswer(28, 9);
    }

    /**
     * @depends testUpdateUserAnswer
     */        
    /*
     * error on purpose => id:24(P:2), delete id:26(P:4), id:28 (P:16)
     */  
    public function testDeleteUserAnswer()
    {    	
    	$this->deleteUserAnswer(24, 2, FALSE);
    	$this->deleteUserAnswer(27, 4);
    	$this->deleteUserAnswer(29, 16);
    }

    /**
     * @depends testDeleteUserAnswer
     */        
    /*
     * error on purpose => id:8, id:13, vote id:1, id:8
     */  
    public function testVoteQuestion()
    {
    	Auth::instance()->logout(TRUE, TRUE);
    	Auth::instance()->login('admin', '12345');
    	
    	$this->voteQuestion(1, 1);
    	$this->voteQuestion(13, 0);
    	$this->voteQuestion(8, 1);
    	$this->voteQuestion(8, 1, FALSE);	// error
    }

    /**
     * @depends testVoteQuestion
     */        
    /*
     * error on purpose => id:23, vote id:22, id:23
     */  
    public function testVoteAnswer()
    {
    	Auth::instance()->logout(TRUE, TRUE);
    	Auth::instance()->login('admin', '12345');
    	
    	$this->voteAnswer(22, 0);
    	$this->voteAnswer(23, 1);
    	$this->voteAnswer(23, 1, FALSE);	// error
    }

    /**
     * @depends testVoteAnswer
     */
    public function testAcceptAnswer()
    {
    	Auth::instance()->logout(TRUE, TRUE);
    	Auth::instance()->login('unittest', '12345');
    	
    	$this->acceptAnswer(25); 
    	$this->acceptAnswer(24, FALSE);	// error: already accepted another answer
    }

    /**
     * @depends testAcceptAnswer
     */      
    /*
     * Add 3 user comments with ids 30 (P:11), 31(P:21), 32(P:26) => error
     */  
    public function testAddCommentUser()
    {
    	$this->addCommentUser(11);
    	$this->addCommentUser(21);
    	$this->addCommentUser(26, FALSE);
    }

    /**
     * @depends testAcceptAnswer
     */      
    /*
     * delete id:29(P:10), id:30 (P:11) => error 
     */  
    public function testDeleteCommentUser()
    {
    	$this->deleteCommentUser(30, 11);
    	$this->deleteCommentUser(31, 11, FALSE);
    }
    
    /* PRIVATE METHODS */
    
    private function addQuestionGuest($index)
    {
    	$_POST = array();
    	$_POST['title'] = "TEST - Question Add $index - guest";
    	$_POST['content'] = "TEST - Question Add $index Content - guest";
    	$_POST['tags'] = 't-' . $index . ',a-' . $index . ',b-' . $index;
    	
    	$result_add = ORM::factory('post')->add_question($_POST);
    	
    	$this->assertSame(URL::title($_POST['title']), $result_add['slug']);
    }
    
    private function addQuestionUser($index)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::QUESTION_ADD);
    	
    	$_POST = array();
    	$_POST['title'] = "TEST - Question Add $index - user";
    	$_POST['content'] = "TEST - Question Add $index Content - user";
    	$_POST['user_id'] = $user->id;
    	$_POST['tags'] = 't-' . $index . ',a-' . $index . ',b-' . $index;
    	
    	$old_rep = $user->reputation;
    	$old_question_count = $user->question_count;
    	
    	$result_add = ORM::factory('post')->add_question($_POST);
    	
    	$this->assertSame(URL::title($_POST['title']), $result_add['slug']);
    	
    	$this->assertSame($user->reputation, $old_rep + $reputation_value);
    	
    	$this->assertSame($user->question_count, $old_question_count + 1);
    }
    
    private function updateUserQuestion($id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::QUESTION_ADD);

    	$old_rep = $user->reputation;
    	$old_question_count = $user->question_count;
    	
    	try {
			$post = $user->get_post_by_id($id, Helper_PostType::QUESTION);
    	}
    	catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('get_post_by_id', $ex->getMessage());	// Raise error
				
			return;
    	}

    	$_POST = array();
    	$_POST['title'] = "TEST - Question EDITED $id - user";
    	$_POST['content'] = "TEST - Question EDITED $id Content - user";
    	$_POST['user_id'] = $user->id;
    	$_POST['tags'] = 'updated-tag';
    	
		try {
    		$result_edit = $post->edit_question($_POST, '');
		}
		catch (Exception $ex) {
			$this->assertEquals('edit_question', $ex->getMessage());	// Raise error
		}
    	
    	$this->assertSame($user->reputation, $old_rep);
    	
    	$this->assertSame($user->question_count, $old_question_count);
    }
        
    private function deleteUserQuestion($id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::QUESTION_ADD);

    	$old_rep = $user->reputation;
    	$old_question_count = $user->question_count;
    	
    	try {
			$post = $user->get_post_by_id($id, Helper_PostType::QUESTION);
    	}
    	catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('get_post_by_id', $ex->getMessage());	// Raise error
				
			return;
    	}
			
		try {
			$post->delete_question();
		}
		catch (Exception $ex) {
			$this->assertEquals('delete_question', $ex->getMessage());	// Raise error
		}
    	
    	$this->assertSame($user->reputation, $old_rep - $reputation_value);
    	
    	$this->assertSame($user->question_count, $old_question_count - 1);
    }
    
    private function addAnswerGuest($parent_id)
    {
    	$_POST = array();
    	//$_POST['title'] = "TEST - Answer For $parent_id th question- guest";
    	$_POST['content'] = "TEST - Answer For $parent_id th question Content - guest";
    	
    	if (($question = ORM::factory('post')->get($parent_id)) === NULL)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);
    	
    	$result_add = ORM::factory('post')->add_answer($_POST, $parent_id);
    	
    	$this->assertSame(TRUE, $result_add);
    	
    	if (($question_updated = ORM::factory('post')->get($parent_id)) === NULL)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);

    	$this->assertSame($question->answer_count + 1, (int) $question_updated->answer_count);
    }
    
    private function addAnswerUser($parent_id)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::ANSWER_ADD);
    	
    	$_POST = array();
    	//$_POST['title'] = "TEST - Answer For $parent_id th question- user";
    	$_POST['content'] = "TEST - Answer For $parent_id th question Content - user";
    	$_POST['user_id'] = $user->id;
    	
    	if (($question = ORM::factory('post')->get($parent_id)) === NULL)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);
    		    	
    	$old_rep = $user->reputation;
    	$old_answer_count = $user->answer_count;
    	
    	$result_add = ORM::factory('post')->add_answer($_POST, $parent_id);
    	
    	$this->assertSame(TRUE, $result_add);
    	
    	$this->assertSame($user->reputation, $old_rep + $reputation_value);
    	
    	$this->assertSame($user->answer_count, $old_answer_count + 1);
    	
    	if (($question_updated = ORM::factory('post')->get($parent_id)) === NULL)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);
    		
    	$this->assertSame($question->answer_count + 1, (int) $question_updated->answer_count);
    }
    
    private function updateUserAnswer($id, $parent_id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();

    	$old_rep = $user->reputation;
    	$old_answer_count = $user->answer_count;
    	
    	try {
			$post = $user->get_post_by_id($id, Helper_PostType::ANSWER);
			
			if ($parent_id != $post->parent_post_id)
				throw new Kohana_Exception(sprintf('Given parent id and post parent id are not equal. given: %d, expected: %d'
                                          , $parent_id, $post->parent_post_id));
    	}
    	catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('get_post_by_id', $ex->getMessage());	// Raise error
				
			return;
    	}

    	$_POST = array();
    	$_POST['content'] = "TEST - Answer EDITED $id, parent_id: $parent_id Content - user";
    	$_POST['user_id'] = $user->id;
    	
		try {
    		$question_slug = $post->edit_answer($_POST);
		}
		catch (Exception $ex) {
			$this->assertEquals('edit_answer', $ex->getMessage());	// Raise error
		}
    	
    	$this->assertNotEquals($question_slug, '');
    	
    	$this->assertSame($user->reputation, $old_rep);
    	
    	$this->assertSame($user->answer_count, $old_answer_count);
    }
    
    private function deleteUserAnswer($id, $parent_id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::ANSWER_ADD);

    	$old_rep = $user->reputation;
    	$old_answer_count = $user->answer_count;
    	
    	try {
			$post = $user->get_post_by_id($id, Helper_PostType::ANSWER);
			
			if ($parent_id != $post->parent_post_id)
				throw new Kohana_Exception(sprintf('Given parent id and post parent id are not equal. given: %d, expected: %d'
                                          , $parent_id, $post->parent_post_id));
    	}
    	catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('get_post_by_id', $ex->getMessage());	// Raise error
				
			return;
    	}
			
		try {
			$post->delete_answer();
		}
		catch (Exception $ex) {
			$this->assertEquals('delete_question', $ex->getMessage());	// Raise error
		}
    	
    	$this->assertSame($user->reputation, $old_rep - $reputation_value);
    	
    	$this->assertSame($user->answer_count, $old_answer_count - 1);
    }
    
    private function voteQuestion($post_id, $vote_type, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	if ($vote_type === 1)
    	{
    		$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::QUESTION_VOTE_UP);
    		$reputation_value_owner = (int) Model_Setting::instance()->get(Helper_ReputationType::OWN_QUESTION_VOTED_UP);
    	}
    	else 
    	{
    		$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::QUESTION_VOTE_DOWN);
    		$reputation_value_owner = (int) Model_Setting::instance()->get(Helper_ReputationType::OWN_QUESTION_VOTED_DOWN);
    	}

    	$old_rep = $user->reputation;
    	
    	if (($post = ORM::factory('post')->get($post_id, Helper_PostType::QUESTION)) === NULL)
    	{
			if ($raise_error)
				$this->assertEquals('voteQuestion', "post not found, ID: $post_id");	// Raise error
				
			return;
    	}

    	if ($post->user_id === $user->id)
		{
			if ($raise_error)
				$this->assertEquals('voteQuestion', 'You cannot vote on your own posts.');	// Raise error
			return;
		}
    	
    	$post_type = Helper_PostType::QUESTION;
    	
        if ($post->user_id != 0)
    	{
    		if (!($owner_user = ORM::factory('user')->get_user_by_id($post->user_id)))
			{
				$this->assertEquals('voteQuestion', 'Owner user couldnt be fetched. ID:' . $post->user_id);	// Raise error
				return;
			}
    	
			$old_rep_owner = $owner_user->reputation;
    	}
			
		try {
			$result = $post->vote_post($vote_type);
		}
		catch (Exception $ex) {
			$this->assertEquals('vote_post', $ex->getMessage());	// Raise error
		}
		

		if ($result !== 1)
		{
			if ($raise_error)
				$this->assertEquals('voteQuestion', 'You already voted.');	// Raise error
			return;
		}

		$this->assertSame($user->reputation, $old_rep + $reputation_value);
    	
        if ($post->user_id != 0)
        {
        	$owner_user = ORM::factory('user')->get_user_by_id($post->user_id);
        	
			$this->assertSame((int) $owner_user->reputation, $old_rep_owner + $reputation_value_owner);
        }
    }
    
    private function voteAnswer($post_id, $vote_type, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	if ($vote_type === 1)
    	{
    		$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::ANSWER_VOTE_UP);
    		$reputation_value_owner = (int) Model_Setting::instance()->get(Helper_ReputationType::OWN_ANSWER_VOTED_UP);
    	}
    	else 
    	{
    		$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::ANSWER_VOTE_DOWN);
    		$reputation_value_owner = (int) Model_Setting::instance()->get(Helper_ReputationType::OWN_ANSWER_VOTED_DOWN);
    	}

    	$old_rep = $user->reputation;
    	
        if (($post = ORM::factory('post')->get($post_id, Helper_PostType::ANSWER)) === NULL)
    	{
			if ($raise_error)
				$this->assertEquals('voteAnswer', "post not found, ID: $post_id");	// Raise error
				
			return;
    	}
    	
    	if ($post->user_id === $user->id)
		{
			if ($raise_error)
				$this->assertEquals('voteAnswer', 'You cannot vote on your own posts.');	// Raise error
			return;
		}
    	
    	$post_type = Helper_PostType::ANSWER;
    	
        if ($post->user_id != 0)
    	{
    		if (!($owner_user = ORM::factory('user')->get_user_by_id($post->user_id)))
			{
				$this->assertEquals('voteAnswer', 'Owner user couldnt be fetched.');	// Raise error
				return;
			}
    	
			$old_rep_owner = $owner_user->reputation;
    	}
			
		try {
			$result = $post->vote_post($vote_type);
		}
		catch (Exception $ex) {
			$this->assertEquals('vote_post', $ex->getMessage());	// Raise error
		}
		
		if ($result !== 1)
		{
			if ($raise_error)
				$this->assertEquals('voteAnswer', 'You already voted.');	// Raise error
			return;
		}
    	
		$this->assertSame($user->reputation, $old_rep + $reputation_value);
    		
        if ($post->user_id != 0)
        {
        	$owner_user = ORM::factory('user')->get_user_by_id($post->user_id);
        	
			$this->assertSame($owner_user->reputation, $old_rep_owner + $reputation_value_owner);
        }
    }
    
    private function acceptAnswer($post_id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();

    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::ACCEPTED_ANSWER);
    	$reputation_value_owner = (int) Model_Setting::instance()->get(Helper_ReputationType::OWN_ACCEPTED_ANSWER);

    	$old_rep = $user->reputation;
    	
        if (($post = ORM::factory('post')->get($post_id, Helper_PostType::ANSWER)) === NULL)
    	{
			if ($raise_error)
				$this->assertEquals('acceptAnswer', "post not found, ID: $post_id");	// Raise error
				
			return;
    	}
    	
    	if ($post->user_id === $user->id)
		{
			if ($raise_error)
				$this->assertEquals('voteAnswer', 'You cannot accept your own posts.');	// Raise error
			return;
		}
    	    	
        if ($post->user_id != 0)
    	{
    		if (!($owner_user = ORM::factory('user')->get_user_by_id($post->user_id)))
			{
				$this->assertEquals('acceptAnswer', 'Owner user couldnt be fetched.');	// Raise error
				return;
			}
    	
			$old_rep_owner = $owner_user->reputation;
    	}
			
		try {
			$result = $post->accept_post();
		}
		catch (Exception $ex) {
			$this->assertEquals('accept_post', $ex->getMessage());	// Raise error
		}
		
		if ($result < 1)
		{
			if ($result === -2 && $raise_error)
				$this->assertEquals('voteAnswer', 'Already Accepted An Answer');	// Raise error
			elseif ($result === -1 && $raise_error)
				$this->assertEquals('voteAnswer', 'Error occured');	// Raise error
				
			return;
		}
    	
    	$this->assertSame($user->reputation, $old_rep + $reputation_value);
    	
        if ($post->user_id != 0)
        {
        	$owner_user = ORM::factory('user')->get_user_by_id($post->user_id);
        	
    		$this->assertSame($owner_user->reputation, $old_rep_owner + $reputation_value_owner);
        }
    }
    
    private function addCommentUser($parent_id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::COMMENT_ADD);
    	
    	$old_rep = $user->reputation;
    	
    	$_POST = array();
    	$_POST['content'] = "TEST - Comment For $parent_id th post Content - user";
    	
    	if (($question = ORM::factory('post')->get($parent_id, Helper_PostType::ALL)) === NULL && $raise_error)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);
    				
		try {
			$add_comment_result = ORM::factory('post')->add_comment($_POST, $parent_id);
		}
		catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('add_comment', $ex->getMessage());	// Raise error
				
			return;
		}
    	
		$this->assertSame(TRUE, $add_comment_result > 0);
		
    	$this->assertSame($user->reputation, $old_rep + $reputation_value);
    	
    	if (($question_updated = ORM::factory('post')->get($parent_id, Helper_PostType::ALL)) === NULL && $raise_error)
    		throw new Kohana_Exception('Post could not be retrieved, ID:' . $parent_id);
    		
    	$this->assertSame($question->comment_count + 1, (int) $question_updated->comment_count);
    }
    
    private function deleteCommentUser($comment_id, $parent_id, $raise_error = TRUE)
    {
    	$user = Auth::instance()->get_user();
    	$reputation_value = (int) Model_Setting::instance()->get(Helper_ReputationType::COMMENT_ADD);
		
    	$old_rep = $user->reputation;
    	
		try {
			$comment = $user->get_post_by_id($comment_id, Helper_PostType::COMMENT);
			$comment->delete_comment();
		}
		catch (Exception $ex) {
			if ($raise_error)
				$this->assertEquals('delete_comment', $ex->getMessage());	// Raise error
				
			return;
		}
    			
    	$this->assertSame($user->reputation, $old_rep - $reputation_value);
    }
    
    private function prepare_db()
    {
    	$current_db = DB::select(array(DB::Expr('DATABASE()'), 'database'))->execute()->current();
    	
    	$test_db = Kohana::config('database')->test;
    	
    	if ($current_db['database'] !== $test_db['connection']['database'])
    	{
    		throw new Kohana_Exception('CURRENT DB IS NOT A TEST DB!!!!!');
    		die();
    	}
    	
    	self::$_empty_db = TRUE;
    	
    	$sql = '';
    	try {
    		$sql = file_get_contents('/var/www/qamini/application/db_changes/03032011_1300_empty_db_script.db');
    		$query_arr = explode(';', $sql);

    		foreach ($query_arr as $q)
    		{
    			if ($q === '')	continue;
    			
	    		DB::query(NULL, $q)->execute();
    		}
    	}
    	catch (Exception $ex) {
    		echo $ex->getMessage();
    	}
    }
}