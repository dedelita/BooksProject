<?php

namespace App\Controller;
//require_once 'vendor/autoload.php';

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\CommentRepository;
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


    private function getResultsGB($service, $q, $maxRes, $startIndex, $lang = null)
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

    private function getGBooksInfo($gbook) 
    {
        $authors = $gbook['volumeInfo']['authors'];
        foreach ($authors as $author) {
            $author = preg_replace("/([A-Z]{1})\-([A-Z]{1}) (.*)/", "$1.$2. $3", $author);
            $author = preg_replace("/([A-Z]{1})\. ([A-Z]{1}\.)(.*)/", "$1.$2 $3", $author);
            $authors_list[] = $author;
        }
        $book = new Book();
        $book->setTitle($gbook['volumeInfo']['title']);
        $book->setAuthor(implode(", ", $authors_list));
        $book->setDescription($gbook['volumeInfo']['description']);
        $book->setImage($gbook['volumeInfo']['imageLinks']['thumbnail']);
        foreach($gbook['volumeInfo']['industryIdentifiers'] as $identifier) {
            if($identifier['type'] == "ISBN_13")
                $book->setIsbn($identifier['identifier']);
        }
        $book->setLanguage($gbook['volumeInfo']['language']);
        return $book;
    }

    /**
     * @Route("/testGApiIsbn", name="gapi_isbn", methods="GET")
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

    private function checkBook($item, $author, $title) {
        return $item['volumeInfo']['imageLinks'] && $item['volumeInfo']['authors'] 
        && (($author && in_array(strtolower($author), array_map("strtolower", $item['volumeInfo']['authors']))) || ($author && preg_grep('/[*]*?' . strtolower($author) . '[*]*?/', array_map("strtolower", $item['volumeInfo']['authors']))) || (!$author))
       && str_contains(strtolower($item['volumeInfo']['title']), strtolower($title));
    }

    /**
     * @Route("/testGApi", name="gapi", methods="GET")
     */
    public function getGBooks($title, $author, $lang) 
    {
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
            if($this->checkBook($item, $author, $title) && $item['volumeInfo']['language'] == $lang)
                $books[] = $this->getGBooksInfo($item);
        }

        if(!$books) {
            foreach ($rgb as $item) {
                if($this->checkBook($item, $author, $title)) {
                    $item['volumeInfo']['language'] = $lang;
                    $books[] = $this->getGBooksInfo($item);
                   }
            }
        }
        return $books;
    }

    /**
     * @Route("/book/{id}", name="book_info")
     */
    public function getBookInfo(Request $request, CommentRepository $commentRepository)
    {
        $book = $this->bookRepository->find($request->get('id'));

        $coms = $commentRepository->findByBook($book->getId());
        return $this->render("user/bookInfo.html.twig", [
            "book" => $book,
            "buttonText" => "read_more",
            "coms" => $coms
        ]);
    }
}
