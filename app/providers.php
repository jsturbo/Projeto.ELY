<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
    //
    public function index()
    {

         $nome = 'João';
         $idade = 30;
         $Sobrenome = 'Silva';
         $data = '12/12/2020';


         $data=[
            'Apelido_nome' => $nome,
            'Idade' => $idade,
            'Sobrenome' => $Sobrenome,
            'Data' => $data
      ];
        // aqui eu poderia criar uma logica para buscar dados do banco de dados e passar para a view
        //verificar se o usuario existe, se tem permissão para acessar a pagina, etc
        //vericar dados do usuario logado, etc

         return view('bemvindo',$data);

    }

    public function index1()
    {
        return view('tchau');
    }
}
