<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property    CI_Form_validation  $form_validation
 * @property    Users               $Users
 * @property    CI_Session          $session
 * @property    CI_DB_query_builder $db
 */
class Handler extends CI_Controller {
    const DefaultValue = 'default';

    private $data = [
        'errors' => [],
    ];

    public function index($page = self::DefaultValue, $subPage = null)
    {
        // Import all helpers and libraries.
        $this->load->helper([
            'url',
            'form',
            'language',
            'tables',
        ]);
        $this->load->model([
            'Users',
        ]);
        $this->load->library([
            'session',
            'form_validation'
        ]);
        $this->lang->load('default', 'english');
        $this->lang->load('application', 'english');

        // Check if the user is logged in
        $this->data['loggedIn'] = $this->session->userId !== NULL;
        $this->data['userId'] = $this->session->userId;
        if($this->data['loggedIn']) {
            $this->data['role'] = $this->Users->userRole($this->data['userId']);
            $this->data['username'] = $this->Users->getUsername($this->data['userId']);
        } else {
            $this->data['role'] = ROLE_VISITOR;
        }

        $pageControllerName = ucfirst($page).'Page';
        $pageControllerFile = './application/pages/'.$pageControllerName.'.php';
        if (file_exists($pageControllerFile)) {
            require_once('./application/controllers/PageFrame.php');
            require_once($pageControllerFile);

            /** @var PageFrame $pageController */
            $pageController = new $pageControllerName();

            if(!$pageController->hasAccess($this->data['role'])) {
                redirect(''); // todo add insufficient rights page
                exit;
            }
            $pageController->setParams([$page, $subPage]);

            $header = $pageController->getHeader();
            $header = $header?$header:[];
            $body = $pageController->getBody();
            $body = $body?$body:[];
            $data = $pageController->getData();
            $data = $data?$data:[];

            $data = array_merge($this->data, $data);

            if(!$header && !$body) {
                redirect('pageNotFound');
            } else {
                $this->load->view('templates/header', $data);
                foreach($header as $h) {
                    $this->load->view('page/'.$h, $data);
                }
                $this->load->view('templates/intersection', $data);
                foreach($body as $b) {
                    $this->load->view('page/'.$b, $data);
                }
                $this->load->view('templates/footer', $data);
            }
        } else {
            if ($page !== 'pageNotFound') {
                redirect('pageNotFound');
            } else {
                show_404();
            }
        }
    }
}