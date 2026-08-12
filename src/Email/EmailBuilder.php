<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Attr\Priority;
use Framework\Builder\Builder;
use Framework\Email\EmailSender;
use Framework\Intl\IntlConfig;
use Framework\Discovery\Package;
use Framework\System\Language;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Email Builder
 * @phpstan-type EmailCodesResult array{
 *   codes: list<string>,
 *   total: int,
 * }
 * @phpstan-type EmailProviderData array{
 *   name:     string,
 *   constant: string,
 *   class:    string,
 * }
 * @phpstan-type EmailProvidersResult array{
 *   providers: list<EmailProviderData>,
 *   none:      string,
 *   total:     int,
 * }
 */
#[Priority(Priority::High)]
class EmailBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $result  = Builder::generateCode("EmailCode", self::collectEmails());
        $result += Builder::generateCode("EmailProvider", self::collectSenders());
        return $result;
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 2;
    }



    /**
     * Collects the Emails from the Emails files
     * @return EmailCodesResult
     */
    public static function collectEmails(): array {
        $languages = Language::getAll();
        $data      = new Dictionary();

        foreach ($languages as $language => $languageName) {
            $data = IntlConfig::loadEmails($language);
            if ($data->isNotEmpty()) {
                break;
            }
        }

        $codes = [];
        foreach ($data as $emailCode => $email) {
            $codes[] = $emailCode;
        }

        // If no codes are found, add a default one
        if (count($codes) === 0) {
            $codes[] = "Test";
        }

        return [
            "codes" => $codes,
            "total" => count($codes),
        ];
    }

    /**
     * Collects the Senders, which are the Providers an Email can go through
     * @return EmailProvidersResult
     */
    public static function collectSenders(): array {
        $classes   = Discovery::findClasses(
            interface:    EmailSender::class,
            forAll:       !Package::isFramework(),
            forFramework: true,
        );

        $providers = [];
        $maxLength = Strings::length("None");

        foreach ($classes as $class) {
            // The name is what EMAIL_PROVIDER takes, and it is the class
            // itself unless the class named itself something else
            $name        = $class->getConstant("Name");
            $name        = $name !== "" ? $name : Strings::substringAfter($class->getName(), "\\");
            $maxLength   = max($maxLength, Strings::length($name));
            $providers[] = [
                "name"     => $name,
                "constant" => $name,
                "class"    => $class->getName(),
            ];
        }

        $providers = Arrays::sortList($providers, function (array $a, array $b) {
            return $a["name"] <=> $b["name"];
        });

        // Pad the names so the arms of the match line up
        foreach ($providers as $index => $provider) {
            $providers[$index]["constant"] = Strings::padRight($provider["name"], $maxLength);
        }

        return [
            "providers" => $providers,
            "none"      => Strings::padRight("None", $maxLength),
            "total"     => count($providers),
        ];
    }
}
