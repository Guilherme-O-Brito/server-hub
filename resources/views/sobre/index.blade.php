@extends('layouts.app')

@section('content')
<div class="container bg-white rounded-lg p-2 mx-auto px-4 mt-5 mb-10 text-left text-gray-800 text-xl space-y-3">
    <h3 class="text-center text-5xl font-bold texte-gray-900">Sobre o Projeto</h3>
    <p>
        Este projeto foi desenvolvido por um estudante do sétimo período de Engenharia de Computação com o objetivo de criar uma plataforma onde amigos possam 
        criar e gerenciar seus próprios servidores de jogos, especialmente aqueles que exigem um servidor dedicado. Atualmente, 
        a plataforma oferece suporte para jogos como <span class="font-semibold">Minecraft</span>, <span class="font-semibold">Assetto Corsa</span> e <span class="font-semibold">Terraria</span>.
    </p>
    <p>
        A ideia central é <span class="font-semibold">facilitar a criação, gestão e hospedagem desses servidores</span>, oferecendo uma solução prática e acessível. 
        A plataforma pode ser mantida online 24 horas por dia, 7 dias por semana, rodando em <span class="font-semibold">mini computadores</span>, como o <span class="font-semibold">Raspberry Pi</span>, 
        ou até mesmo em <span class="font-semibold">desktops que estejam sem uso</span>, desde que possuam o desempenho mínimo necessário para suportar os jogos desejados.
    </p>
    <p>
        Para garantir uma boa performance, é recomendado que o computador que hospeda a plataforma tenha, no mínimo, <span class="font-semibold">8 GB de RAM</span>, 
        sendo <span class="font-semibold">16 GB de RAM</span> a configuração ideal. Quanto ao processador, recomenda-se pelo menos um <span class="font-semibold">Intel i5 ou Ryzen 5</span>, sendo ainda melhor 
        se for um <span class="font-semibold">Intel i7 ou Ryzen 7</span>. É possível executar a plataforma em processadores inferiores; no entanto, o desempenho em alguns jogos pode ser limitado.
    </p>
    <p>
        O principal objetivo deste projeto é oferecer aos jogadores <span class="font-semibold">maior controle sobre a hospedagem de seus servidores</span>, 
        proporcionando <span class="font-semibold">uma alternativa mais econômica e com melhor custo-benefício</span> em comparação com serviços tradicionais de hospedagem.
    </p>
    <p>
        Além disso, a plataforma foi projetada com foco em <span class="font-semibold">segurança</span>, oferecendo opções de <span class="font-semibold">whitelist, 
        autenticação de usuários e gerenciamento de permissões</span>, garantindo que apenas jogadores autorizados possam acessar os servidores. 
        Também é <span class="font-semibold">compatível com certificados SSL</span>, permitindo a utilização do <span class="font-semibold">protocolo HTTPS</span> para maior segurança durante o acesso e a 
        administração dos servidores.
    </p>
    <p>
        Mesmo para usuários sem conhecimentos técnicos avançados, o sistema conta com uma <span class="font-semibold">interface intuitiva e amigável</span>, facilitando 
        o processo de <span class="font-semibold">configuração e manutenção dos servidores</span>. A <span class="font-semibold">customização</span> também é um ponto forte, permitindo aos usuários escolher mods (limitado até certo ponto), 
        mapas e configurações específicas conforme suas preferências.
    </p>
    <h3 class="text-center text-5xl font-bold texte-gray-900">Tecnologias Utilizadas</h3>
    <p>
        O backend da plataforma foi desenvolvido utilizando o <span class="font-semibold">Laravel</span>, que gerencia desde a hospedagem do próprio site até funcionalidades mais complexas, 
        como o <span class="font-semibold">monitoramento em tempo real</span> e a <span class="font-semibold">gestão dos servidores de jogos</span>. Para o frontend, foram utilizadas as frameworks <span class="font-semibold">TailwindCSS e Vue.js</span>, 
        sendo esta última fundamental para a <span class="font-semibold">integração de comunicação ao vivo com os consoles dos servidores</span>, garantindo um feedback imediato ao usuário.
    </p>
    <p>
        Como banco de dados, foi utilizado o <span class="font-semibold">MySQL</span>, mais especificamente o fork <span class="font-semibold">MariaDB</span>, garantindo maior compatibilidade e desempenho em sistemas <span class="font-semibold">Linux</span>. 
        O servidor responsável pela comunicação web é o <span class="font-semibold">Apache</span>, que permite uma <span class="font-semibold">configuração simples</span> e é totalmente <span class="font-semibold">compatível com SSL</span>, 
        facilitando a habilitação de <span class="font-semibold">conexões seguras via HTTPS</span>.
    </p>
    <p>
        O projeto foi desenvolvido para ser <span class="font-semibold">facilmente configurável</span>, permitindo que qualquer pessoa com conhecimentos básicos de informática 
        consiga instalar e gerenciar sua própria instância da plataforma, sem depender de serviços de terceiros.
    </p>
</div>
@endsection