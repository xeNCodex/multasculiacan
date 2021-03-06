<?php

namespace App\Console\Commands;

use App\Multa;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\AssignOp\Mul;

class LeerMulta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multas:leer {folio}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imprime el html de una multa';
    protected $ayuntamiento;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $client = new \GuzzleHttp\Client(["verify" => false]);
        $this->ayuntamiento = new \Goutte\Client();
        $this->ayuntamiento->setClient($client);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new Client();
        $folio_number = $this->argument('folio');
        if (Multa::whereFolio("J". $folio_number)->exists()
            OR
            DB::table("failed_attempts")->whereFolio($folio_number)->exists()
        ){
//            info("El folio ". $this->argument('folio'). "ya existe");
        } else {
            return $this->requestMultaInfo();

        }

    }

    /**
     * @return bool
     */
    public function requestMultaInfo()
    {
        $response = $this->ayuntamiento->request("GET", "https://pagos.culiacan.gob.mx/multas-transito/" . $this->argument('folio'), ["verify" => false]);
        try {
            $folio = $response->filter('body > div.datos-boleta > div > dl > dd')->eq(0)->html();

            $placa = $response->filter('body > div.datos-boleta > div > dl > dd')->eq(1)->html();
            $importe = $response->filter('body > div.datos-boleta > div > dl > dd')->eq(2)->html();
            $redondeo = $response->filter('body > div.datos-boleta > div > dl > dd')->eq(3)->html();

            $multas_html = $response->filter('tbody')->html();
            $html = $response->html();
            $multa = [
                'folio' => $folio,
                'placa' => $placa,
                'importe' => $importe,
                'redondeo' => $redondeo,
                'multas_html' => $multas_html,
                'html' =>  ""
            ];
//        $multa = Multa::firstOrCreate(
//            ["folio" => $this->argument('folio')],
//            $multa
//        );
            $multa = Multa::create($multa);

//            $this->info($multa->placa);
//            $this->info($multa->folio);
//            $this->info($multa->importe);
//            $this->info($multa->redondeo);
//            $this->info($multa->multas_html);
//        $this->info($multa->html);
            return true;

        } catch (\Exception $e) {
            DB::table("failed_attempts")->updateOrInsert(
                ['folio' => $this->argument('folio') ],
                ["folio" => $this->argument('folio'),
                    "created_at" => Carbon::now()]
            );
//            $this->output = false;
        }
    }
}
