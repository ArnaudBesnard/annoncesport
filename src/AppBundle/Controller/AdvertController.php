<?php
namespace AppBundle\Controller;
use AppBundle\Entity\Advert;
use AppBundle\Entity\Departments;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
//use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;

class AdvertController extends Controller
{

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $adverts = $em->getRepository('AppBundle:Advert')->findBy(
            array('published' => 1), // Critere
            array('postedAt' => 'desc'),        // Tri
            6,                              // Limite
            0                               // Offset
        );
        return $this->render('advert/index.html.twig', array(
            'adverts' => $adverts,
        ));
    }

    public function showAllAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $adverts = $em->getRepository('AppBundle:Advert')->findBy(
            array('published' => 1), // Critere
            array('postedAt' => 'desc')
        );
        $advert = $this->get('knp_paginator')->paginate(
            $adverts,
            $request->query->get('page', 1)/*le numéro de la page à afficher*/, 4/*nbre d'éléments par page*/
        );
        return $this->render('advert/showAll.html.twig', array(
            'advert' => $advert,
        ));
    }

    public function searchAction(Request $request){
        $form = $this->createForm('AppBundle\Form\SearchType');
        $form->handleRequest($request);
        $cat = $form['category']->getData();
        $title = $form['title']->getData();
        $city = $form['city']->getData();
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('a')
            ->from(Advert::class, 'a')
            ->where('a.category = :category')
                ->setParameter('category', $cat)
            ->andWhere('a.title LIKE :title OR a.content LIKE :title')
                ->setParameter('title', '%'.$title.'%')
            ->andWhere('a.city = :city OR a.department LIKE :city')
                ->setParameter('city', $city);
        $query = $queryBuilder->getQuery();
        $adverts = $query->getResult();

        $advert = $this->get('knp_paginator')->paginate(
            $adverts,
            $request->query->get('page', 1)/*le numéro de la page à afficher*/, 4/*nbre d'éléments par page*/
        );
        return $this->render('advert/search.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }

    public function newAction(Request $request)
    {
        $advert = new Advert();
        $form = $this->createForm('AppBundle\Form\AdvertType', $advert);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();
            return $this->redirectToRoute('advert_show', array('id' => $advert->getId()));
        }
        return $this->render('advert/new.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }

    public function showAction(Advert $advert)
    {
        $deleteForm = $this->createDeleteForm($advert);
        $advertCity = $advert->getCity();
        $em = $this->getDoctrine()->getManager();
        $city = $em->getRepository('AppBundle:City')->findOneBy(
            array('realName' => $advertCity)
        );
        return $this->render('advert/show.html.twig', array(
            'advert' => $advert,
            'city' => $city,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function editAction(Request $request, Advert $advert)
    {
        $deleteForm = $this->createDeleteForm($advert);
        $editForm = $this->createForm('AppBundle\Form\AdvertType', $advert);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('advert_show', array('id' => $advert->getId()));
        }
        return $this->render('advert/edit.html.twig', array(
            'advert' => $advert,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function deleteAction(Request $request, Advert $advert)
    {
        $form = $this->createDeleteForm($advert);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($advert);
            $em->flush();
        }
        return $this->redirectToRoute('advert_index');
    }
    private function createDeleteForm(Advert $advert)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('advert_delete', array('id' => $advert->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    public function menuAction()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Advert');
        $listAdverts = $repository->findAll();
        foreach ($listAdverts as $advert) {
            // $advert est une instance de Advert
            //echo $advert->getContent();
            return $this->render('advert/menu.html.twig', array(
                // Tout l'intérêt est ici : le contrôleur passe
                // les variables nécessaires au template !
                'listAdverts' => $listAdverts
            ));
        }
    }

    public function viewCategoriesAction()
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Categories');
        $listCategories = $repository->findAll();
        foreach ($listCategories as $category) {
            // $advert est une instance de Advert
            //echo $advert->getContent();
            return $this->render('advert/categories.html.twig', array(
                // Tout l'intérêt est ici : le contrôleur passe
                // les variables nécessaires au template !
                'listCategories' => $listCategories
            ));
        }
    }

    public function advByCatAction(Request $request, $catName){

        $em = $this->getDoctrine()->getManager();
        $cat = $em->getRepository('AppBundle:Categories')->findOneBy(['category' => $catName]);
        $adverts = $em->getRepository('AppBundle:Advert')->findBy(
            array('category' => $cat), // Critere
            array('postedAt' => 'desc') // Tri
        );
        $advert = $this->get('knp_paginator')->paginate(
            $adverts,
            $request->query->get('page', 1)/*le numéro de la page à afficher*/, 4/*nbre d'éléments par page*/
        );
        return $this->render('advert/showAll.html.twig', array(
            'advert' => $advert,
        ));
    }

    public function userAdvertsAction(request $request){
        $user = $this->getUser()->getUsername();
        $em = $this->getDoctrine()->getManager();
        $adverts = $em->getRepository('AppBundle:Advert')->findBy(
            array('author' => $user), // Critere
            array('postedAt' => 'desc')
        );
        $advert = $this->get('knp_paginator')->paginate(
            $adverts,
            $request->query->get('page', 1)/*le numéro de la page à afficher*/, 4/*nbre d'éléments par page*/
        );
        return $this->render('advert/showAll.html.twig', array(
            'advert' => $advert,
        ));
    }

    public function countAdvertAction(){
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('COUNT(a.id)')
            ->from(Advert::class, 'a')
            ->where('a.published = :published')
            ->setParameter('published', 1);
        $query = $queryBuilder->getQuery();
        $count = $query->getSingleScalarResult();
        return new Response($count);
    }


}