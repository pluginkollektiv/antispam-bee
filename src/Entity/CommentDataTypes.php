<?php
declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Entity;

class CommentDataTypes
{

    const COMMENT = 'comment';
    const TRACKBACK = 'trackback';
    const PINGBACK = 'pingback';
    const PING = 'ping';
    const ALL = [
        self::COMMENT,
        self::TRACKBACK,
        self::PINGBACK,
        self::PING,
    ];

    const PINGS = [
        self::TRACKBACK,
        self::PING,
        self::PINGBACK,
    ];
}