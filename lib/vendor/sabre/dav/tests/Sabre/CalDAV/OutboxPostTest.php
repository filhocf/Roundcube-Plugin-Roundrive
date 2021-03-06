<?php

namespace Sabre\CalDAV;
use Sabre\HTTP;
use Sabre\VObject;
use Sabre\DAV;

require_once 'Sabre/DAVServerTest.php';
require_once 'Sabre/CalDAV/Schedule/IMip/Mock.php';

class OutboxPostTest extends \Sabre\DAVServerTest {

    protected $setupCalDAV = true;
    protected $setupACL = true;
    protected $autoLogin = 'user1';

    function testPostPassThruNotFound() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/notfound',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $this->assertHTTPStatus(501, $req);

    }

    function testPostPassThruNotTextCalendar() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
        ));

        $this->assertHTTPStatus(501, $req);

    }

    function testPostPassThruNoOutBox() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $this->assertHTTPStatus(501, $req);

    }

    function testNoOriginator() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/calendars/user1/outbox',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testNoRecipient() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testBadOriginator() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'nomailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(403, $req);

    }

    function testBadOriginator2() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(403, $req);

    }

    function testBadRecipient() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1@example.org',
            'HTTP_RECIPIENT'  => 'http://user1@example.org, mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testIncorrectOriginator() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/admin/outbox',
            'HTTP_ORIGINATOR' => 'mailto:orig@example.org',
            'HTTP_RECIPIENT'  => 'mailto:user1@example.org, mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );
        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(403, $req);

    }

    function testInvalidIcalBody() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));
        $req->setBody('foo');

        $this->assertHTTPStatus(400, $req);

    }

    function testNoVEVENT() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'BEGIN:VTIMEZONE',
            'END:VTIMEZONE',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testNoMETHOD() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(400, $req);

    }

    function testUnsupportedMethod() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $this->assertHTTPStatus(501, $req);

    }

    function testNoIMIPHandler() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));


        $response = $this->request($req);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '5.2;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);


    }

    function testSuccessRequest() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '2.0;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Invitation for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REQUEST; charset=utf-8',
                    'X-Sabre-Version: ' . DAV\Version::VERSION,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessRequestUseRelativePrincipal() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => '/principals/user1/',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'ORGANIZER:mailto:user1.sabredav@sabredav.org',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals(200, $response->status, 'Full body: ' . $response->body);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '2.0;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Invitation for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REQUEST; charset=utf-8',
                    'X-Sabre-Version: ' . DAV\Version::VERSION,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessRequestUpperCased() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'MAILTO:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'MAILTO:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(array(
            'Content-Type' => 'application/xml',
        ), $response->headers);

        // Lazily checking the body for a few expected values.
        $this->assertTrue(strpos($response->body, '2.0;')!==false);
        $this->assertTrue(strpos($response->body,'user2@example.org')!==false);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Invitation for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REQUEST; charset=utf-8',
                    'X-Sabre-Version: ' . DAV\Version::VERSION,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessReply() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REPLY',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);
        $this->assertHTTPStatus(200, $req);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Response for: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=REPLY; charset=utf-8',
                    'X-Sabre-Version: ' . DAV\Version::VERSION,
                ),
           )
        ), $handler->getSentEmails());

    }

    function testSuccessCancel() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => 'mailto:user1.sabredav@sabredav.org',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:CANCEL',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);
        $this->assertHTTPStatus(200, $req);

        $this->assertEquals(array(
            array(
                'to' => 'user2@example.org',
                'subject' => 'Cancelled event: An invitation',
                'body' => implode("\r\n", $body) . "\r\n",
                'headers' => array(
                    'Reply-To: user1.sabredav@sabredav.org',
                    'From: server@example.org',
                    'Content-Type: text/calendar; method=CANCEL; charset=utf-8',
                    'X-Sabre-Version: ' . DAV\Version::VERSION,
                ),
           )
        ), $handler->getSentEmails());


    }

    function testUseRelativePrincipalNoFallback() {

        $req = HTTP\Sapi::createFromServerArray(array(
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/calendars/user1/outbox',
            'HTTP_ORIGINATOR' => '/principals/user1/',
            'HTTP_RECIPIENT'  => 'mailto:user2@example.org',
            'HTTP_CONTENT_TYPE' => 'text/calendar',
        ));

        $body = array(
            'BEGIN:VCALENDAR',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'SUMMARY:An invitation',
            'ORGANIZER:rrrrrr',
            'END:VEVENT',
            'END:VCALENDAR',
        );

        $req->setBody(implode("\r\n",$body));

        $handler = new Schedule\IMip\Mock('server@example.org');

        $this->caldavPlugin->setIMIPhandler($handler);

        $response = $this->request($req);
        $this->assertEquals(403, $response->status, 'Full body: ' . $response->body);

    }
}
