<?php
namespace Framework\Tools;

use Framework\Console;
use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\Composer;
use Framework\Discovery\Package;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\Utils\Strings;

/**
 * The Framework Release
 */
class Release {

    private const Branch  = "dev";
    private const Release = "main";


    /**
     * Releases the Framework by doing:
     * - Moving the version (--patch moves the patch version instead of the minor)
     * - Committing the version
     * - Tagging the version
     * - Moving the release branch to the new version
     * - Pushing the version, tag, and release branch to origin (if --push is given)
     * @param bool $patch Optional.
     * @param bool $push  Optional.
     * @return void
     */
    #[ConsoleCommand("release", isPrivate: true)]
    #[NotTested("It is one of the Tools")]
    public static function make(bool $patch = false, bool $push = false): void {
        if (!self::isOnBranch() || !self::isClean()) {
            return;
        }

        $current = self::currentVersion();
        $version = Version::moveVersion($current, 1, patch: $patch);
        if ($version === "") {
            print("The version can not be moved from $current\n");
            return;
        }
        if (self::hasTag(self::tagFor($version))) {
            print("The tag " . self::tagFor($version) . " is already there\n");
            return;
        }

        print("Releasing $version\n");
        if (!self::runChecks()) {
            return;
        }
        if (!self::writeVersion($version)) {
            return;
        }
        if (!self::commit($version)) {
            return;
        }

        self::report($version, push: $push);
    }



    /**
     * Returns true if the release is being made from the branch it is made from
     * @return bool
     */
    private static function isOnBranch(): bool {
        [ $code, $output ] = self::git("rev-parse", "--abbrev-ref", "HEAD");
        $branch = $code === 0 ? Strings::toString($output[0] ?? "") : "";

        if ($branch === self::Branch) {
            return true;
        }

        print("A release is made from " . self::Branch . ", and this is $branch\n");
        return false;
    }

    /**
     * Returns true if there is nothing waiting to be committed
     * @return bool
     */
    private static function isClean(): bool {
        // The commit further down takes everything it finds, so it has to be
        // sure that is only the version
        [ $code, $output ] = self::git("status", "--porcelain");
        if ($code === 0 && count($output) === 0) {
            return true;
        }

        print("There is work in the tree, so the version would not be committed on its own:\n");
        foreach ($output as $line) {
            print("  $line\n");
        }
        return false;
    }

    /**
     * Returns true if the given Tag is already in the repository
     * @param string $tag
     * @return bool
     */
    private static function hasTag(string $tag): bool {
        [ $code ] = self::git("rev-parse", "--quiet", "--verify", "refs/tags/$tag");
        return $code === 0;
    }



    /**
     * Runs everything that has to pass before a version is written
     * @return bool
     */
    private static function runChecks(): bool {
        // The build first, since the generated code is gitignored and a checkout
        // without it fails every check for the wrong reason
        $checks = [
            "Building"      => "./framework build",
            "PHPStan"       => "vendor/bin/phpstan analyse --no-progress",
            "PHPCS"         => "vendor/bin/phpcs src",
            "PHPUnit"       => "vendor/bin/phpunit",
            "Documentation" => "./framework docsCheck",
        ];

        foreach ($checks as $name => $command) {
            print("- $name\n");
            if (!self::run($command)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs the given command, and prints what it said only when it failed
     * @param string $command
     * @return bool
     */
    private static function run(string $command): bool {
        $basePath = Package::getBasePath();
        $output   = [];
        $code     = 0;

        exec("cd " . escapeshellarg($basePath) . " && $command 2>&1", $output, $code);
        if ($code === 0) {
            return true;
        }

        print("  It failed, so nothing was written:\n");
        foreach ($output as $line) {
            print("  $line\n");
        }
        return false;
    }



    /**
     * Writes the given Version everywhere it is recorded
     * @param string $version
     * @return bool
     */
    private static function writeVersion(string $version): bool {
        // Read back rather than trusted: the writing is a set of regular
        // expressions, and one that matches nothing says so only in passing
        Version::writeVersion($version);

        if (self::currentVersion() === $version) {
            return true;
        }

        print("The version was not written, so the release stops here\n");
        return false;
    }

    /**
     * Commits the version, tags it, and moves the release branch to this one
     * @param string $version
     * @return bool
     */
    private static function commit(string $version): bool {
        $tag = self::tagFor($version);

        $steps = [
            [ "commit", "-a", "-m", "Version $version" ],
            [ "tag", $tag ],
            [ "branch", "--force", self::Release, self::Branch ],
        ];

        foreach ($steps as $step) {
            [ $code, $output ] = self::git(...$step);
            if ($code === 0) {
                continue;
            }

            print("git " . implode(" ", $step) . " failed:\n");
            foreach ($output as $line) {
                print("  $line\n");
            }
            return false;
        }
        return true;
    }

    /**
     * Says what was done, and either pushes it or leaves the command to run
     * @param string $version
     * @param bool   $push
     * @return void
     */
    private static function report(string $version, bool $push): void {
        $tag    = self::tagFor($version);
        $remote = "origin " . self::Branch . " " . self::Release . " $tag";

        print("- Committed, tagged $tag, and moved " . self::Release . "\n");

        if (!$push) {
            print("\nNothing has left the repository. To publish it:\n");
            print("  git push $remote\n");
            return;
        }
        if (!Console::confirm("Push $tag to origin")) {
            print("Left unpushed. To publish it: git push $remote\n");
            return;
        }

        [ $code, $output ] = self::git("push", "origin", self::Branch, self::Release, $tag);
        if ($code !== 0) {
            print("The push failed:\n");
            foreach ($output as $line) {
                print("  $line\n");
            }
            return;
        }
        print("- Pushed\n");
    }



    /**
     * Returns the Version the repository is at, read fresh from the composer file
     * @return string
     */
    private static function currentVersion(): string {
        // Not Application::getVersion(), which keeps the first answer it gave
        return Composer::readFile(Package::getBasePath())->version;
    }

    /**
     * Returns the Tag of the given Version
     * @param string $version
     * @return string
     */
    private static function tagFor(string $version): string {
        return "v$version";
    }

    /**
     * Runs git with the given arguments, and returns what happened
     * @param string ...$args
     * @return array{int,list<string>}
     */
    private static function git(string ...$args): array {
        $basePath = Package::getBasePath();
        $command  = "git";
        $output   = [];
        $code     = 0;

        foreach ($args as $arg) {
            $command .= " " . escapeshellarg($arg);
        }

        exec("cd " . escapeshellarg($basePath) . " && $command 2>&1", $output, $code);
        return [ $code, $output ];
    }
}
