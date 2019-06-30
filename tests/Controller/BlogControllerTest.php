<?php

namespace App\Tests\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for the controllers defined inside BlogController.
 *
 * See https://symfony.com/doc/current/book/testing.html#functional-tests
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(
            Post::NUM_ITEMS,
            $crawler->filter('article.post'),
            'The homepage displays the right number of posts.'
        );
    }

    public function testRss()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rss.xml');

        self::assertSame(
            'text/xml; charset=UTF-8',
            $client->getResponse()->headers->get('Content-Type')
        );

        self::assertCount(
            Post::NUM_ITEMS,
            $crawler->filter('item'),
            'The xml file displays the right number of posts.'
        );
    }

    /**
     * This test changes the database contents by creating a new comment. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testNewComment()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->followRedirects();

        // Find first blog post
        $crawler = $client->request('GET', '/');
        $postLink = $crawler->filter('article.post > h2 a')->link();

        $crawler = $client->click($postLink);

        $form = $crawler->selectButton('Publish comment')->form([
            'comment[content]' => 'Hi, Symfony!',
        ]);
        $crawler = $client->submit($form);

        $newComment = $crawler->filter('.post-comment')->first()->filter('div > p')->text();

        self::assertSame('Hi, Symfony!', $newComment);
    }

    public function testAjaxSearch()
    {
        $client = static::createClient();
        $client->xmlHttpRequest('GET', '/search', ['q' => 'lorem']);

        $results = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        self::assertCount(1, $results);
        self::assertSame('Lorem ipsum dolor sit amet consectetur adipiscing elit', $results[0]['title']);
        self::assertSame('Jane Doe', $results[0]['author']);
    }
}
