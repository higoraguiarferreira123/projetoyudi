<?php

/* =========================================================
   QUESTSYSTEM
   SIDEBAR GLOBAL
   ========================================================= */


/* ---------------------------------------------------------
   GARANTIR SESSÃO
--------------------------------------------------------- */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/* ---------------------------------------------------------
   DADOS DO USUÁRIO
--------------------------------------------------------- */

$tipo_usuario = $_SESSION['tipo'] ?? '';

$nome_usuario = $_SESSION['nome'] ?? 'Usuário';

$pagina_atual = basename($_SERVER['PHP_SELF']);


/* ---------------------------------------------------------
   DEFINIR INICIAIS DO USUÁRIO
--------------------------------------------------------- */

$iniciais = '';

$partes_nome = preg_split(
    '/\s+/',
    trim($nome_usuario)
);

if (count($partes_nome) >= 2) {

    $iniciais =
        mb_substr(
            $partes_nome[0],
            0,
            1,
            'UTF-8'
        )
        .
        mb_substr(
            $partes_nome[count($partes_nome) - 1],
            0,
            1,
            'UTF-8'
        );

} elseif ($nome_usuario !== '') {

    $iniciais =
        mb_substr(
            $nome_usuario,
            0,
            2,
            'UTF-8'
        );

}

$iniciais = mb_strtoupper(
    $iniciais,
    'UTF-8'
);


/* ---------------------------------------------------------
   FUNÇÃO DE PÁGINA ATIVA
--------------------------------------------------------- */

function menuAtivo($paginas)
{
    global $pagina_atual;

    if (!is_array($paginas)) {
        $paginas = [$paginas];
    }

    return in_array(
        $pagina_atual,
        $paginas,
        true
    )
        ? 'ativo'
        : '';
}


/* ---------------------------------------------------------
   ESCAPE
--------------------------------------------------------- */

function sidebarEscapar($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>


<!-- =======================================================
     SIDEBAR
======================================================= -->

<aside class="sidebar">


    <!-- ===================================================
         MARCA
    ==================================================== -->

    <div class="sidebar-logo">

        <div class="sidebar-brand-top">

            <div class="sidebar-brand-icon">
                Q
            </div>

            <div class="sidebar-brand-text">

                <div class="sidebar-brand-name">
                    QuestSystem
                </div>

                <div class="sidebar-brand-subtitle">
                    Sistema de avaliações
                </div>

            </div>

        </div>

    </div>


    <!-- ===================================================
         NAVEGAÇÃO
    ==================================================== -->

    <nav class="sidebar-menu">


        <?php if ($tipo_usuario === 'professor') { ?>


            <!-- =========================================
                 DASHBOARD
            ========================================== -->

            <a
                href="dashboard.php"
                class="<?php
                    echo menuAtivo(
                        ['dashboard.php']
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    ⌂
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- =========================================
                 BANCO DE QUESTÕES
            ========================================== -->

            <a
                href="questoes.php"
                class="<?php
                    echo menuAtivo(
                        ['questoes.php']
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    📚
                </span>

                <span>
                    Banco de Questões
                </span>

            </a>


            <!-- =========================================
                 CRIAR PROVA
            ========================================== -->

            <a
                href="criar_prova.php"
                class="<?php
                    echo menuAtivo(
                        ['criar_prova.php']
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    📝
                </span>

                <span>
                    Criar Prova
                </span>

            </a>


            <!-- =========================================
                 PROVA COM IA
            ========================================== -->

            <a
                href="criar_prova_ia.php"
                class="<?php
                    echo menuAtivo(
                        ['criar_prova_ia.php']
                    );
                ?>"
            >

                <span class="sidebar-icone sidebar-ia">
                    ✦
                </span>

                <span class="sidebar-link-content">

                    <span>
                        Prova com IA
                    </span>

                    <small>
                        Inteligência Artificial
                    </small>

                </span>

            </a>


            <!-- =========================================
                 RESULTADOS
            ========================================== -->

            <a
                href="resultados.php"
                class="<?php
                    echo menuAtivo(
                        [
                            'resultados.php',
                            'gabarito_aluno.php',
                            'analise_ia.php'
                        ]
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    📊
                </span>

                <span>
                    Resultados
                </span>

            </a>


        <?php } elseif ($tipo_usuario === 'aluno') { ?>


            <!-- =========================================
                 DASHBOARD ALUNO
            ========================================== -->

            <a
                href="dashboard.php"
                class="<?php
                    echo menuAtivo(
                        ['dashboard.php']
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    ⌂
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- =========================================
                 PROVAS
            ========================================== -->

            <a
                href="provas.php"
                class="<?php
                    echo menuAtivo(
                        [
                            'provas.php',
                            'realizar_prova.php'
                        ]
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    📝
                </span>

                <span>
                    Minhas Provas
                </span>

            </a>


            <!-- =========================================
                 HISTÓRICO
            ========================================== -->

            <a
                href="historico.php"
                class="<?php
                    echo menuAtivo(
                        [
                            'historico.php',
                            'resultado_prova.php'
                        ]
                    );
                ?>"
            >

                <span class="sidebar-icone">
                    📈
                </span>

                <span>
                    Histórico
                </span>

            </a>


        <?php } ?>


    </nav>


    <!-- ===================================================
         ÁREA DO USUÁRIO
    ==================================================== -->

    <div class="sidebar-bottom">


        <div class="sidebar-user">


            <!-- AVATAR -->

            <div class="sidebar-avatar">

                <?php
                echo sidebarEscapar(
                    $iniciais
                );
                ?>

            </div>


            <!-- INFORMAÇÕES -->

            <div class="sidebar-user-info">

                <strong>

                    <?php
                    echo sidebarEscapar(
                        $nome_usuario
                    );
                    ?>

                </strong>

                <span>

                    <?php

                    echo
                        $tipo_usuario === 'professor'
                            ? 'Professor'
                            : 'Aluno';

                    ?>

                </span>

            </div>


        </div>


        <!-- =================================================
             SAIR
        ================================================== -->

        <a
            href="../auth/logout.php"
            class="sidebar-sair"
        >

            <span class="sidebar-icone">
                ⇥
            </span>

            <span>
                Sair do sistema
            </span>

        </a>


    </div>


</aside>


<!-- =======================================================
     ESTILO ESPECÍFICO DO SIDEBAR
======================================================= -->

<style>

/* =========================================================
   MARCA
========================================================= */

.sidebar-brand-top{

    display:flex;

    align-items:center;

    gap:11px;

}


.sidebar-brand-icon{

    width:40px;

    height:40px;

    flex:0 0 40px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:12px;

    color:#fff;

    background:
        linear-gradient(
            135deg,
            #4267c7,
            #3192b0
        );

    font-size:17px;

    font-weight:900;

    box-shadow:
        0 8px 18px
        rgba(0,0,0,.16);

}


.sidebar-brand-text{

    min-width:0;

}


.sidebar-brand-name{

    color:#fff;

    font-size:17px;

    font-weight:900;

    letter-spacing:-.3px;

}


.sidebar-brand-subtitle{

    margin-top:1px;

    color:
        rgba(255,255,255,.52);

    font-size:9px;

}


/* =========================================================
   LINKS
========================================================= */

.sidebar-menu a{

    position:relative;

}


.sidebar-menu a .sidebar-icone{

    width:31px;

    height:31px;

    flex:0 0 31px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:9px;

    background:
        rgba(255,255,255,.07);

    font-size:14px;

    transition:.2s ease;

}


/* LINK ATIVO */

.sidebar-menu a.ativo{

    background:
        linear-gradient(
            135deg,
            rgba(65,95,185,.95),
            rgba(39,125,151,.95)
        ) !important;

    box-shadow:
        0 10px 22px
        rgba(7,25,55,.20);

}


/* INDICADOR LATERAL */

.sidebar-menu a.ativo::before{

    content:"";

    position:absolute;

    left:-15px;

    top:9px;

    width:3px;

    height:27px;

    border-radius:0 4px 4px 0;

    background:#8fd8e9;

}


/* ÍCONE ATIVO */

.sidebar-menu a.ativo .sidebar-icone{

    background:
        rgba(255,255,255,.15);

}


/* =========================================================
   LINK IA
========================================================= */

.sidebar-ia{

    color:#fff !important;

    background:
        linear-gradient(
            135deg,
            rgba(110,76,170,.55),
            rgba(42,130,155,.50)
        ) !important;

}


/* =========================================================
   TEXTO DO LINK IA
========================================================= */

.sidebar-link-content{

    display:flex;

    flex-direction:column;

    min-width:0;

}


.sidebar-link-content small{

    margin-top:1px;

    color:
        rgba(255,255,255,.48);

    font-size:8px;

    font-weight:600;

}


.sidebar-menu a.ativo
.sidebar-link-content small{

    color:
        rgba(255,255,255,.68);

}


/* =========================================================
   PARTE INFERIOR
========================================================= */

.sidebar-bottom{

    margin-top:auto;

    padding-top:15px;

    border-top:
        1px solid
        rgba(255,255,255,.08);

}


/* =========================================================
   USUÁRIO
========================================================= */

.sidebar-user{

    display:flex;

    align-items:center;

    gap:10px;

    padding:
        7px 8px 11px;

}


.sidebar-avatar{

    width:37px;

    height:37px;

    flex:0 0 37px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    color:#fff;

    background:

        linear-gradient(
            135deg,
            #496aca,
            #3190ad
        );

    font-size:11px;

    font-weight:900;

    box-shadow:
        0 7px 15px
        rgba(0,0,0,.14);

}


.sidebar-user-info{

    min-width:0;

    display:flex;

    flex-direction:column;

}


.sidebar-user-info strong{

    color:#fff;

    font-size:10px;

    line-height:1.3;

    white-space:nowrap;

    overflow:hidden;

    text-overflow:ellipsis;

}


.sidebar-user-info span{

    margin-top:1px;

    color:
        rgba(255,255,255,.50);

    font-size:9px;

}


/* =========================================================
   SAIR
========================================================= */

.sidebar-sair{

    display:flex;

    align-items:center;

    gap:10px;

    width:100%;

    padding:
        10px 8px;

    border-radius:9px;

    color:
        rgba(255,255,255,.67) !important;

    text-decoration:none;

    font-size:10px;

    font-weight:800;

    transition:.2s ease;

}


.sidebar-sair:hover{

    color:#fff !important;

    background:
        rgba(255,255,255,.07);

}


.sidebar-sair .sidebar-icone{

    width:29px;

    height:29px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:8px;

    background:
        rgba(255,255,255,.06);

    font-size:14px;

}


/* =========================================================
   RESPONSIVO
========================================================= */

@media(max-width:950px){

    .sidebar-logo{

        padding-left:0;

        padding-right:0;

    }


    .sidebar-brand-top{

        justify-content:center;

    }


    .sidebar-brand-text{

        display:none;

    }


    .sidebar-menu a{

        justify-content:center;

        padding:
            10px 8px;

    }


    .sidebar-menu a > span:last-child{

        display:none;

    }


    .sidebar-menu a .sidebar-icone{

        width:32px;

        height:32px;

    }


    .sidebar-menu a.ativo::before{

        left:-9px;

    }


    .sidebar-user{

        justify-content:center;

        padding-left:0;

        padding-right:0;

    }


    .sidebar-user-info{

        display:none;

    }


    .sidebar-sair{

        justify-content:center;

    }


    .sidebar-sair > span:last-child{

        display:none;

    }

}

</style>

