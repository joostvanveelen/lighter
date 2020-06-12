<?php
namespace Lighter\Environment\Type\DockerCompose;
use PHPUnit\Framework\TestCase;
class OutputParserTest extends TestCase
{
    /**
     * @dataProvider PSOutputs
     *
     * @param string $text
     * @param Service[] $expectedServices
     */
    public function testParsePS($text, $expectedServices)
    {
        $lines = explode("\n", $text);
        $outputParser = new OutputParser();
        $services = $outputParser->parsePS($lines);
        $this->assertCount(3, $services);
        foreach ($expectedServices as $i => $expectedService) {
            $testedService = $services[$i];
            $this->assertEquals(
                str_replace(' ', '', $expectedService->getName()),
                str_replace(' ', '', $testedService->getName())
            );
            $this->assertEquals(
                str_replace(' ', '', $expectedService->getCommand()),
                str_replace(' ', '', $testedService->getCommand())
            );
            $this->assertEquals(
                str_replace(' ', '', $expectedService->getState()),
                str_replace(' ', '', $testedService->getState())
            );
            $this->assertEquals(
                str_replace(' ', '', $expectedService->getPorts()),
                str_replace(' ', '', $testedService->getPorts())
            );
        }
    }
    public function PSOutputs()
    {
        $expected = [
            new Service(
                'development-containers_platformproxy_1',
                'nginx -g daemon off;',
                'Failed',
                '80/tcp'
            ),
            new Service(
                'development-containers_rabbitmq_1',
                'docker-entrypoint.sh rabbi ...',
                'Up',
                '15671/tcp, 0.0.0.0:15672->15672/tcp, 25672/tcp, 4369/tcp, 5671/tcp, 0.0.0.0:5672->5672/tcp'
            ),
            new Service(
                'development-containers_traefik_1',
                '/entrypoint.sh --api --doc ...',
                'Up',
                '0.0.0.0:80->80/tcp, 0.0.0.0:8080->8080/tcp'
            ),
        ];
        $width24 = <<< EOT
Name   Comm   Stat   Por
       and     e     ts
------------------------
deve   ngin   Fail   80/
lopm   x -g   ed     tcp
ent-   daem
cont   on
aine   off;
rs_p
latf
ormp
roxy
_1
deve   dock   Up     156
lopm   er-e          71/
ent-   ntry          tcp
cont   poin          , 0
aine   t.sh          .0.
rs_r   rabb          0.0
abbi   i             :15
tmq_   ...           672
1                    ->1
                     567
                     2/t
                     cp,
                     256
                     72/
                     tcp
                     , 4
                     369
                     /tc
                     p,
                     567
                     1/t
                     cp,
                     0.0
                     .0.
                     0:5
                     672
                     ->5
                     672
                     /tc
                     p
deve   /ent   Up     0.0
lopm   rypo          .0.
ent-   int.          0:8
cont   sh -          0->
aine   -api          80/
rs_t   --do          tcp
raef   c             , 0
ik_1   ...           .0.
                     0.0
                     :80
                     80-
                     >80
                     80/
                     tcp
EOT;
        $width38 = <<< EOT
  Name     Command    State    Ports
--------------------------------------
developm   nginx -g   Faile   80/tcp
ent-cont   daemon     d
ainers_p   off;
latformp
roxy_1
developm   docker-e   Up      15671/tc
ent-cont   ntrypoin           p, 0.0.0
ainers_r   t.sh               .0:15672
abbitmq_   rabbi              ->15672/
1          ...                tcp, 256
                              72/tcp,
                              4369/tcp
                              , 5671/t
                              cp, 0.0.
                              0.0:5672
                              ->5672/t
                              cp
developm   /entrypo   Up      0.0.0.0:
ent-cont   int.sh             80->80/t
ainers_t   --api              cp, 0.0.
raefik_1   --doc              0.0:8080
           ...                ->8080/t
                              cp
EOT;
        $width80 = <<< EOT
         Name                   Command         State           Ports
--------------------------------------------------------------------------------
development-containers   nginx -g daemon off;   Failed   80/tcp
_platformproxy_1
development-             docker-entrypoint.sh   Up       15671/tcp, 0.0.0.0:156
containers_rabbitmq_1    rabbi ...                       72->15672/tcp,
                                                         25672/tcp, 4369/tcp,
                                                         5671/tcp,
                                                         0.0.0.0:5672->5672/tcp
development-             /entrypoint.sh --api   Up       0.0.0.0:80->80/tcp,
containers_traefik_1     --doc ...                       0.0.0.0:8080->8080/tcp
EOT;
        $width114 = <<< EOT
               Name                             Command               State                  Ports
-----------------------------------------------------------------------------------------------------------------
development-                         nginx -g daemon off;             Failed   80/tcp
containers_platformproxy_1
development-containers_rabbitmq_1    docker-entrypoint.sh rabbi ...   Up       15671/tcp,
                                                                               0.0.0.0:15672->15672/tcp,
                                                                               25672/tcp, 4369/tcp, 5671/tcp,
                                                                               0.0.0.0:5672->5672/tcp
development-containers_traefik_1     /entrypoint.sh --api --doc ...   Up       0.0.0.0:80->80/tcp,
                                                                               0.0.0.0:8080->8080/tcp
EOT;
        $widthFull = <<< EOT
                 Name                               Command               State                                             Ports
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
development-containers_platformproxy_1   nginx -g daemon off;             Failed   80/tcp
development-containers_rabbitmq_1        docker-entrypoint.sh rabbi ...   Up       15671/tcp, 0.0.0.0:15672->15672/tcp, 25672/tcp, 4369/tcp, 5671/tcp, 0.0.0.0:5672->5672/tcp
development-containers_traefik_1         /entrypoint.sh --api --doc ...   Up       0.0.0.0:80->80/tcp, 0.0.0.0:8080->8080/tcp
EOT;
        return [
            [$width24, $expected],
            [$width38, $expected],
            [$width80, $expected],
            [$width114, $expected],
            [$widthFull, $expected],
        ];
    }
}