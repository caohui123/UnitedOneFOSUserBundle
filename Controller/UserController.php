<?php

namespace United\OneFOSUserBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use United\OneBundle\Controller\TableController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use United\OneFOSUserBundle\Form\ChangePasswordUserType;
use United\OneFOSUserBundle\Form\CreateUserType;
use United\OneFOSUserBundle\Form\UpdateUserType;

abstract class UserController extends TableController
{

    /**
     * Returns the template for the given action. For the base implementation,
     * $action can be: index|create|update|delete.
     *
     * @param string $action the action to get the twig template for
     * @return string the twig template to render
     */
    protected function getTemplateForAction($action)
    {
        switch ($action) {
            case 'row':
                return 'UnitedOneFOSUserBundle:User:row.html.twig';
                break;
            default:
                return parent::getTemplateForAction($action);
                break;
        }
    }

    /**
     * Returns the form the given action. For the base implementation,
     * $action can be: index|create|update|delete.
     *
     * @param string $action
     * @param null|object $entity
     * @return string|Form
     */
    protected function getFormForAction($action, $entity = null)
    {
        if ($action == 'create') {
            return $this->createForm(new CreateUserType($this->getEntityRepository()->getClassName()), $entity);
        }

        if ($action == 'update' ) {
            return $this->createForm(new UpdateUserType($this->getEntityRepository()->getClassName()), $entity);
        }

        if($action == 'change') {
            return $this->createForm(new ChangePasswordUserType($this->getEntityRepository()->getClassName()), $entity);
        }

        return parent::getFormForAction($action, $entity);
    }

    /**
     * Renders the update form and processes form data if available.
     *
     * @Route("/{id}/change")
     * @Method({"GET", "POST"})
     *
     * @param mixed $id
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function changeAction($id, Request $request)
    {
        $this->checkActionAccess(); // Check if we can access this action

        // Create an update form for the entity
        if (!$entity = $this->findEntityById($id)) {
            throw $this->createNotFoundException('Entity with id: ' . $id . ' not found');
        }

        if(!$this->get('security.authorization_checker')->isGranted('change', $entity)) {
            throw new AccessDeniedHttpException('You are not allowed to change the password for Entity: "' . $entity . '"!');
        }

        if (!$form = $this->getFormForAction('change', $entity)) {
            throw new \Exception(
                'You must define a form for the change action by implementing getFormForAction().'
            );
        }

        $form->handleRequest($request);

        // If form is valid, we save the entity and redirect to indexAction.
        if ($form->isValid()) {

            // Save the entity
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($entity);

            // Call success method for this action
            return $this->getSuccessForAction('update');
        } // If form is not valid, we need to render the update form
        else {
            $context = array(
                'entity' => $entity,
                'form' => $form->createView(),
                'errors' => $form->getErrors(true)
            );
            $this->alterContextForAction('update', $context);
            return $this->render($this->getTemplateForAction('update'), $context);
        }
    }
}