<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class QuizQuestionController extends BaseController
{
	public function indexAction(Request $request, $courseId)
	{
		$course   = $this->getCourseService()->tryManageCourse($courseId);
		$lessons  = $this->getCourseService()->getCourseLessons($courseId);
		
		$field    = $request->query->all();

		$question = array();
		if (empty($field['parentId'])){

			$conditions['parentId'] = $field['parentId'] = 0;

			if(empty($field['target'])){
				$conditions['target']['course'] = array($courseId);

				if (!empty($lessons)){
					$conditions['target']['lesson'] = ArrayToolkit::column($lessons,'id');;
				}

			}else{
				list($targetType, $targetId) = explode('-', $field['target']);

				$conditions['target'][$targetType] = array($targetId);
			}

		} else {

			$question = $this->getQuestionService()->getQuestion($field['parentId']);

			if (empty($question)){
				return $this->redirect($this->generateUrl('course_manage_quiz_question',array('courseId' => $courseId)));
			}

			$conditions['parentId'] = $field['parentId'];
			
		}

		if(!empty($field['questionType'])){
			$conditions['questionType'] = $field['questionType'];
		}

		if(!empty($field['keyword'])){
			$conditions['stem'] = $field['keyword'];
		}

		$paginator = new Paginator(
			$this->get('request'),
			$this->getQuestionService()->searchQuestionCount($conditions),
			10
		);

		$questions = $this->getQuestionService()->searchQuestion(
			$conditions,
			array('createdTime' ,'DESC'),
			$paginator->getOffsetCount(),
            $paginator->getPerPageCount()
		);

		$lessons = ArrayToolkit::index($lessons,'id');
		$users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'userId')); 
		$targets = $this->findQuestionTargets($courseId);
		
		return $this->render('TopxiaWebBundle:QuizQuestion:index.html.twig', array(
			'course' => $course,
			'questions' => $questions,
			'users' => $users,
			'lessons' => $lessons,
			'targets' => $targets,
			'question' => $question,
			'paginator' => $paginator,
			'field' => $field,
		));
	}

	public function createAction(Request $request, $courseId, $type)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);
		if (!in_array($type, array('choice','single_choice', 'fill', 'material', 'essay', 'determine'))) {
			throw $this->createNotFoundException('该项目问题类型不存在');
		}
		$parentId = $request->query->get('parentId');

		if (empty($parentId)){
			$parentId = 0;
		} else {
			$question = $this->getQuestionService()->getQuestion($parentId);
			if (empty($question)){
				return $this->redirect($this->generateUrl('course_manage_quiz_question',array('courseId' => $courseId)));
			}
		}

		$targets = $this->findQuestionTargets($courseId);

		$category = $this->getQuestionService()->findCategorysByCourseIds(array($courseId));

	    if ($request->getMethod() == 'POST') {
            $question = $request->request->all();
            $question['parentId'] = $parentId;
	        $question = $this->getQuestionService()->createQuestion($question);

			$submission = $request->request->get('submission');
	        if ($submission == 'continue'){
	        	
	        	$default = array(
	        		'courseId' => $courseId,
	        		'targetsDefault' => $question['targetType'].'-'.$question['targetId'],
	        		'questionDifficulty' => $question['difficulty'],
	        		'type' => $type,
	        		'parentId' => $parentId,
	        	);

	        	$this->setFlashMessage('success', '题目添加成功，请继续添加！');

	            return $this->redirect($this->generateUrl('course_manage_quiz_question_create',$default));

	        } else if ($submission == 'submit'){

	        	$this->setFlashMessage('success', '题目添加成功！');

		        if ($type == 'material'){
					$parentId = $question['id'];
				}

	        	return $this->redirect($this->generateUrl('course_manage_quiz_question',array('courseId' => $courseId,'parentId' => $parentId)));
	        }
        }

		$targets['default'] = $request->query->get('targetsDefault');

		$question['difficulty'] = $request->query->get('questionDifficulty');

		return $this->render('TopxiaWebBundle:QuizQuestion:modal.html.twig', array(
			'course' => $course,
			'type' => $type,
			'targets' => $targets,
			'parentId' => $parentId,
			'question' => $question,
			'category' => $category,
		));
	}


	public function editAction(Request $request, $courseId, $id)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);
		$question = $this->getQuestionService()->getQuestion($id);
		if (empty($question)){
			throw $this->createNotFoundException('该项目问题问题不存在');
		}
		$targets = $this->findQuestionTargets($courseId);

		$category = $this->getQuestionService()->findCategorysByCourseIds(array($courseId));

	    if ($request->getMethod() == 'POST') {

            $question = $request->request->all();

	        $question = $this->getQuestionService()->updateQuestion($id, $question);

	        $this->setFlashMessage('success', '题目修改成功！');

			return $this->redirect($this->generateUrl('course_manage_quiz_question',array('courseId'=>$courseId,'parentId' => $question['parentId'])));
        }

		$choice = array();
        if ($question['questionType'] =='choice' || $question['questionType'] =='single_choice'){
        	$choice = $question['choice'];
        	unset($question['choice']);
        }

        $targets['default'] = $question['targetType'].'-'.$question['targetId'];
        $category['default'] = $question['categoryId'];
        
        return $this->render('TopxiaWebBundle:QuizQuestion:modal.html.twig', array(
			'question' => $question,
			'targets' => $targets,
			'course' => $course,
			'choice' => $choice,
			'type' => $question['questionType'],
			'isEdit' => '1',
			'category' => $category,
		));
	}

	public function categoryAction(Request $request, $courseId)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);
		$category =	$this->getQuestionService()->findCategorysByCourseIds(array($courseId));
        return $this->render('TopxiaWebBundle:QuizQuestionCategory:index.html.twig', array(
			'categorys' => $category,
			'course' => $course,
        ));
    }

    public function createCategoryAction(Request $request, $courseId)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);
		if ($request->getMethod() == 'POST') {

			$field =$request->request->all();
			$field['courseId'] = $courseId;
            $category = $this->getQuestionService()->createCategory($field);

            return $this->render('TopxiaWebBundle:QuizQuestionCategory:tr.html.twig', array(
				'category' => $category,
				'course' => $course
	        ));
        }
        return $this->render('TopxiaWebBundle:QuizQuestionCategory:modal.html.twig', array(
            'course' => $course,
        ));
    }

    public function updateCategoryAction(Request $request, $courseId, $categoryId)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);
		$category = $this->getQuestionService()->getCategory($categoryId);
		if ($request->getMethod() == 'POST') {
			$field = $request->request->all();

            $category = $this->getQuestionService()->updateCategory($categoryId, $field);
            return $this->render('TopxiaWebBundle:QuizQuestionCategory:tr.html.twig', array(
				'category' => $category,
				'course' => $course,
	        ));
        }
        return $this->render('TopxiaWebBundle:QuizQuestionCategory:modal.html.twig', array(
            'category' => $category,
            'course' => $course,
        ));
    }

    public function sortCategoriesAction(Request $request, $courseId)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);

		$this->getQuestionService()->sortCategories($course['id'], $request->request->get('ids'));
		return $this->createJsonResponse(true);
	}

    public function deleteCategoryAction(Request $request, $courseId, $categoryId)
    {
		$course = $this->getCourseService()->tryManageCourse($courseId);
        $category = $this->getQuestionService()->getCategory($categoryId);
        if (empty($category)) {
            throw $this->createNotFoundException();
        }
        $this->getQuestionService()->deleteCategory($categoryId);
        return $this->createJsonResponse(true);
    }

	public function deleteAction(Request $request, $courseId, $id)
    {
		$course = $this->getCourseService()->tryManageCourse($courseId);
		$question = $this->getQuestionService()->getQuestion($id);
        if (empty($question)) {
            throw $this->createNotFoundException('question not found');
        }
        $this->getQuestionService()->deleteQuestion($id);
        return $this->createJsonResponse(true);
    }

    public function deletesAction(Request $request, $courseId)
    {   
		$course = $this->getCourseService()->tryManageCourse($courseId);
        $ids = $request->request->get('ids');
        if(empty($ids)){
        	throw $this->createNotFoundException();
        }
        foreach ($ids as $id) {
        	$this->getQuestionService()->deleteQuestion($id);
        }
        return $this->createJsonResponse(true);
    }

    public function uploadFileAction (Request $request, $courseId, $type)
    {
    	$course = $this->getCourseService()->getCourse($courseId);

    	if ($request->getMethod() == 'POST') {
	    	$originalFile = $this->get('request')->files->get('file');
	    	$file = $this->getUploadFileService()->addFile('quizquestion', 0, array('isPublic' => 1), 'local', $originalFile);
	    	return $this->createJsonResponse($file);
	    }
    }

    private function findQuestionTargets($courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        if (empty($course))
            return null;
        $lessons = $this->getCourseService()->getCourseLessons($courseId);

        $targets = array();

        $targets['course'.'-'.$course['id']] = '课程';

        foreach ($lessons as  $lesson) {
            $targets['lesson'.'-'.$lesson['id']] = '课时'.$lesson['number'].'-'.$lesson['title'];
        }

        return $targets;
    }


	private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

   	private function getQuestionService()
   	{
   		return $this -> getServiceKernel()->createService('Quiz.QuestionService');
   	}

   	private function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

}
