<?php

namespace JA\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormTypeInterface;

use JA\AppBundle\Form\Type\NewsType;
use JA\AppBundle\Exception\InvalidFormException;

class NewsController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get all the news. @todo: doc !
     * Empty list if there's no news
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets all news",
     *   output = "JA\AppBundle\Entity\News",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Rest\View(
     *      templateVar="news"
     * )
     *
     * @return News array
     *
     */
    public function cgetAction()
    {
        $news = $this->getNewsHandler()->getAll();

        return $news;
    }

    /**
     * Get single News. @todo: doc !
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets a news for a given id",
     *   output = "JA\AppBundle\Entity\News",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the news is not found"
     *   }
     * )
     *
     * @Rest\View(
     *      templateVar="news"
     * )
     *
     * @param unsigned int $id the news id
     *
     * @return News
     *
     * @throws NotFoundHttpException when news not exist
     */
    public function getAction($id)
    {
        if(!($news = $this->getNewsHandler()->get($id))) {
            throw $this->createNotFoundException("The resource '". $id ."' was not found.");
        }

        return $news;
    }

    /**
     * Presents the form to use to create a news.
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Rest\View()
     *
     * @return FormTypeInterface
     */
    public function newAction()
    {
        /*if(false === $this->get('security.authorization_checker')->isGranted('create'))
        {
            throw $this->createAccessDeniedException();
        }*/

        return $this->createForm(new NewsType(), null, array('action' => $this->generateUrl('api_1_post_news')));
    }

    /**
     * Create a news from submitted data. @todo: doc !
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Create a news from data sent",
     *   output = "",
     *   statusCodes = {
     *     201 = "Returned when successful",
     *     204 = "Data already exists",
     *     400 = "The data sent is not valid",
     *     422 = "The news data sent contains errors"
     *   }
     * )
     *
     * If the template is returned, you have a bad request
     * @Rest\View(
     *      template="JAAppBundle:News:new.html.twig",
     *      statusCode = Codes::HTTP_BAD_REQUEST
     * )
     *
     * @param Request $request
     *
     * @return FormTypeInterface|View
     */
    public function postAction(Request $request)
    {
        /*if(false === $this->get('security.authorization_checker')->isGranted('create'))
        {
            $this->get('logger')->debug('{user} can\'t create game.', array('user' => $this->get('security.token_storage')->getToken()->getUser()->getUsername()));
            throw $this->createAccessDeniedException();
        }*/

        try
        {
            // News handler create a News.
            $newNews = $this->getNewsHandler()->post(
                $request->request->get(NewsType::NAME)
            );

            $routeOptions = array(
                'id' => $newNews->getId()
            );

            $view = $this->routeRedirectView('api_1_get_news', $routeOptions, Codes::HTTP_CREATED);
            $view->setData($newNews); // we send the data to avoid multiple requests

            return $view;
        }
        catch(InvalidFormException $exception)
        {
            return $exception->getForm();
        }
    }

    /**
     * Presents the form to use to edit a News.
     *
     * @ApiDoc(
     *   resource = false,
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "When the News was not found"
     *   }
     * )
     *
     * @Rest\View()
     *
     * @param unsigned int $id The news id to edit
     *
     * @return FormTypeInterface
     *
     * @throws NotFoundHttpException
     */
    public function editAction($id)
    {
        if(!$news = $this->getNewsHandler()->get($id))
            throw $this->createNotFoundException('The resource ' . $id . ' was not found.');

        /*if(false === $this->get('security.authorization_checker')->isGranted('edit', $game))
            throw $this->createAccessDeniedException();
        */

        $form = $this->createForm(
            new NewsType(),
            $news,
            array(
                'action' => $this->generateUrl(
                        'api_1_put_news',
                        array('id' => $news->getId())
                    ),
                'method' => 'put'
            )
        );

        return $form;
    }

    /**
     * Edit or create a news from submitted data. @todo: doc !
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Edit or create a News from data sent",
     *   output = "",
     *   statusCodes = {
     *     201 = "Returned when the data doesn't exist already",
     *     204 = "Returned when successful",
     *     400 = "The data sent is not valid",
     *     422 = "The news data sent contains errors"
     *   }
     * )
     *
     * If the template is returned, you have a bad request
     * @Rest\View(
     *      template="JAAppBundle:News:edit.html.twig",
     *      statusCode = Codes::HTTP_BAD_REQUEST
     * )
     *
     * @param Request $request
     * @param string $id The identifiers of the news
     *
     * @return FormTypeInterface|View
     */
    public function putAction(Request $request, $id)
    {
        try
        {
            // if data doesn't exist, we create it
            $formName = $request->request->get(NewsType::NAME);
            if(!$news = $this->getNewsHandler()->get($id))
            {
                $code = Codes::HTTP_CREATED;
                $news = $this->getNewsHandler()->post(
                    $formName
                );
            }
            else
            {
                /*if(false === $this->get('security.authorization_checker')->isGranted('edit', $game))
                    throw $this->createAccessDeniedException();*/
                $code = Codes::HTTP_NO_CONTENT;
                $news = $this->getNewsHandler()->put(
                    $news,
                    $formName
                );
            }

            $routeOptions = array(
                'id' => $news->getId()
            );

            $view = $this->routeRedirectView('api_1_get_news', $routeOptions, $code);
            if($code === Codes::HTTP_CREATED)
                $view->setData($news); // we send the data to avoid multiple requests

            return $view;
        }
        catch(InvalidFormException $exception)
        {
            return $exception->getForm();
        }
    }

    /**
     * Edit partially a News from submitted data. @todo: doc !
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Edit partially a News from data sent",
     *   output = "",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     400 = "The data sent is not valid",
     *     404 = "The news was not found",
     *     422 = "The news data sent contains errors"
     *   }
     * )
     *
     * If the template is returned, you have a bad request
     * @Rest\View(
     *      template="",
     *      statusCode = Codes::HTTP_BAD_REQUEST
     * )
     *
     * @param Request $request
     * @param string $id The news identifier
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException
     */
    public function patchAction(Request $request, $id)
    {
        try
        {
            // if data doesn't exist, we create it
            if($news = $this->getNewsHandler()->get($id))
            {
                /*if(false === $this->get('security.authorization_checker')->isGranted('edit', $game))
                    throw $this->createAccessDeniedException();*/

                $news = $this->getNewsHandler()->patch(
                    $news,
                    $request->request->get(NewsType::NAME)
                );
            }
            else
                throw $this->createNotFoundException('The resource ' . $id . ' was not found.');

            $routeOptions = array(
                'id' => $news->getId()
            );

            $view = $this->routeRedirectView('api_1_get_news', $routeOptions, Codes::HTTP_NO_CONTENT);

            return $view;
        }
        catch(InvalidFormException $exception)
        {
            return $exception->getForm();
        }
    }

    /**
     * Get a form to delete a news. @todo: doc !
     *
     * @ApiDoc(
     *   resource = false,
     *   description = "Get a form to delete a news",
     *   output = "",
     *   statusCodes = {
     *     204 = "Returned when successful",
     *     404 = "The data sent is not valid"
     *   }
     * )
     *
     * @Rest\View(
     *      template="JAAppBundle:News:remove.html.twig",
     *      templateVar="form"
     * )
     *
     * @param unsigned int $id The News identifier
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException
     */
    public function removeAction($id)
    {
        if(!$news = $this->getNewsHandler()->get($id))
            $this->createNotFoundException();

        /*if(false === $this->get('security.authorization_checker')->isGranted('delete', $game))
            throw $this->createAccessDeniedException();*/

        $deleteForm = $this->createDeleteForm($id);

        return $deleteForm;
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->add('Delete', 'submit')
            ->setAction($this->generateUrl('api_1_delete_news', array('id' => $id)))
            ->setMethod('delete')
            ->getForm()
            ;
    }

    /**
     * Delete a news. @todo: doc !
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Delete a News",
     *   output = "",
     *   statusCodes = {
     *     204 = "Returned when successful"
     *   }
     * )
     *
     * @todo: See for the redirection after success
     * @Rest\View(
     *      template="JAAppBundle:News:remove.html.twig",
     * )
     *
     * @param unsigned int $id The slug to identify the game
     *
     * @return FormTypeInterface|View
     *
     * @throws NotFoundHttpException
     */
    public function deleteAction($id)
    {
        if($news = $this->getNewsHandler()->get($id))
        {
            /*if(false === $this->get('security.authorization_checker')->isGranted('delete', $game))
            {
                $this->get('logger')->debug('User can\'t delete this game');
                throw $this->createAccessDeniedException();
            }*/

            $this->getNewsHandler()->delete($news);
        }

        $view = $this->routeRedirectView('api_1_cget_news', array(), Codes::HTTP_NO_CONTENT);

        return $view;
    }

    private function getNewsHandler()
    {
        return $this->container->get('ja_app.news.handler');
    }
}
