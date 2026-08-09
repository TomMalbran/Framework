<?php
namespace Tests\Analysis\Fixture\Route;

use Framework\Discovery\Attr\Route;
use Framework\IO\Request;
use Framework\IO\Response;
use Framework\System\Access;

class DuplicateRoutes {

    #[Route("/crates/create", Access::General)]
    public static function create(Request $request): Response {
        return Response::success("CREATED");
    }

    #[Route("/crates/create", Access::General)]
    public static function createAgain(Request $request): Response {
        return Response::success("CREATED");
    }

    #[Route("/crates/edit", Access::General)]
    public static function edit(Request $request): Response {
        return Response::success("EDITED");
    }
}
