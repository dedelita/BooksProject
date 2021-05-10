<?php

namespace App\Controller;
//require_once 'vendor/autoload.php';

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class BookController extends AbstractController
{
    private $bookRepository;
    private $client;
    
    public function __construct(BookRepository $bookRepository)
    {
        $this->client = new \Google\Client();
        $this->bookRepository = $bookRepository;
    }


    public function getResultsGB($service, $q, $maxRes, $startIndex, $lang = null)
    {
        if($lang != null)
            return $service->volumes->listVolumes(['q' => $q], [
                'maxResults' => $maxRes, 
                'startIndex' => $startIndex,
                'langRestrict' => $lang,
                'printType' => "books"
            ]);
        
        return $service->volumes->listVolumes(['q' => $q], [
            'maxResults' => $maxRes, 
            'startIndex' => $startIndex,
            'printType' => "books"
        ]);
    }

    public function getGBooksInfo($gbook) {
        $book = new Book();
        $book->setTitle($gbook['volumeInfo']['title']);
        $book->setAuthor(implode(", ",$gbook['volumeInfo']['authors']));
        $book->setDescription($gbook['volumeInfo']['description']);
        if($gbook['volumeInfo']['imageLinks'])
               $book->setImage($gbook['volumeInfo']['imageLinks']['thumbnail']);
        foreach($gbook['volumeInfo']['industryIdentifiers'] as $identifier) {
            if($identifier['type'] == "ISBN_13")
                $book->setIsbn($identifier['identifier']);
        }
        $book->setLanguage($gbook['volumeInfo']['language']);
        return $book;
    }

    /**
     * @Route("/testGApiIsbn", name="test_gapi_isbn", methods="GET")
     */
    public function getGBooksByIsbn($isbn) 
    {
        $this->client->setApplicationName($this->getParameter("app_name"));
        $this->client->setDeveloperKey($this->getParameter("api_key"));
        $service = new \Google_Service_Books($this->client);
        $results = $this->getResultsGB($service, "isbn:$isbn", 40, 0);
        $rgb = $results->getItems();
        
        return $this->getGBooksInfo($rgb[0]);
    }

    /**
     * @Route("/testGApi", name="test_gapi", methods="GET")
     */
    public function getGBooks($title, $author, $lang) 
    {
        //$title = "la cour des hiboux";
        //$author = "snyder";
        $this->client->setApplicationName($this->getParameter("app_name"));
        $this->client->setDeveloperKey($this->getParameter("api_key"));
        $service = new \Google_Service_Books($this->client);
        $startIndex = 0;

        $q = "";
        if($title)
            $q.= "intitle:\"" . str_replace(' ', '+', $title) . "\"";
        if($author)
            $q.= "+inauthor:\"" . str_replace(' ', '+', $author) . "\"";

        $results = $this->getResultsGB($service, $q, 40, $startIndex, $lang);
        $rgb = $results->getItems();
        
        if($results['totalItems'] > 40) {
            $iteratorNb = intdiv($results['totalItems'], 40);
            for ($i=0; $i < $iteratorNb; $i++) { 
                $startIndex += 39;
                $results = $this->getResultsGB($service, $q, 40, $startIndex, $lang);
                $rgb = array_merge($rgb, $results->getItems());
            }
        }
        $books = [];
        foreach ($rgb as $item) {
            if( $item['volumeInfo']['authors'] && strtolower($item['volumeInfo']['title']) == strtolower($title))
                $books[] = $this->getGBooksInfo($item);;
        }
        return $books;
    }
}
