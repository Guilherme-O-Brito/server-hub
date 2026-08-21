# 🇧🇷 Server-Hub

O **Server-Hub** é uma plataforma criada para abstrair a complexidade da auto-hospedagem de servidores de jogos, permitindo que usuários comuns, mesmo sem conhecimento técnico sobre containers, Kubernetes ou administração de servidores, possam criar e gerenciar seus próprios servidores por meio de uma interface simples e amigável.

A proposta é tornar todo o processo o mais **plug and play** possível: a infraestrutura é preparada pelo administrador, enquanto os usuários podem iniciar, parar e gerenciar seus servidores sem precisar conhecer os detalhes técnicos por trás deles.

Um dos principais diferenciais do Server-Hub é ter sido pensado para funcionar como uma **plataforma compartilhada dentro de um grupo de amigos**. Em vez de o servidor depender diretamente da disponibilidade de uma única pessoa, todos podem acessar a plataforma e gerenciar os servidores aos quais possuem permissão.

Na prática, a ideia é resolver situações comuns como:

> **"Precisamos criar um servidor para jogar."**
> **"O cara que hospeda o servidor não pode jogar hoje."**

Com o Server-Hub, o servidor deixa de depender de quem tradicionalmente seria *"o cara do servidor"* e passa a ser um recurso compartilhado, centralizado e acessível pelo grupo.

---

## Índice

* [Por que usar o Server-Hub?](#por-que-usar-o-server-hub)
* [Guia de instalação e primeiros passos](#guia-de-instalação-e-primeiros-passos)
* [Avisos e recomendações de segurança](#avisos-e-recomendações-de-segurança)
* [Versionamento](#versionamento)
* [Arquitetura e tecnologias](#arquitetura-e-tecnologias)
* [Jogos suportados](#jogos-suportados)
* [Licença](#licença)

---

## Por que usar o Server-Hub?

O principal objetivo do **Server-Hub** é resolver o clássico problema do *"cara do servidor"*: aquela pessoa do grupo que sabe configurar e hospedar o servidor e, por isso, acaba sendo responsável por mantê-lo funcionando sempre que os amigos querem jogar.

O Server-Hub separa essa responsabilidade em duas partes. Alguém ainda precisa ser o **administrador da plataforma** e possuir um pouco mais de conhecimento técnico para realizar o deploy inicial, configurar a infraestrutura e administrar os recursos disponíveis. Depois disso, porém, os detalhes técnicos ficam abstraídos para os demais usuários.

O administrador pode criar uma conta para cada pessoa do grupo e, a partir daí, os usuários podem acessar uma interface amigável para criar e gerenciar servidores dos jogos suportados pela plataforma, sem precisar saber como configurar containers, Kubernetes ou a infraestrutura utilizada por trás deles.

Para hospedar o Server-Hub é necessário manter uma máquina responsável pela plataforma e pelos servidores de jogos. Como a ideia é que eles estejam disponíveis independentemente da presença de uma pessoa específica, essa máquina deve permanecer ligada enquanto o serviço precisar estar disponível.

Ela pode ser, por exemplo, um **computador mais antigo que esteja sobrando** ou até mesmo uma **VPS cujo custo seja dividido entre os amigos**.

### Servidores realmente compartilhados

Um dos principais diferenciais do Server-Hub é permitir que **mais de uma pessoa tenha permissão para iniciar, parar e administrar o mesmo servidor**.

Isso elimina situações comuns em jogos que utilizam mundos hospedados por um jogador, em que existe um *"dono do mundo"* ou *"dono do servidor"* e todos dependem dessa pessoa estar disponível para jogar.

No Server-Hub, um servidor pode ter um proprietário e outros usuários autorizados a administrá-lo. Dessa forma, qualquer pessoa com a permissão necessária pode iniciar o servidor quando o grupo quiser jogar.

### Simplicidade para os usuários, controle para o administrador

A facilidade para os usuários não significa abrir mão do controle sobre a infraestrutura. O administrador da plataforma continua responsável por definir os recursos que estarão disponíveis para o grupo.

Por exemplo, o administrador pode criar **slots de execução** que determinam quantos servidores podem ficar ativos simultaneamente. Dependendo da capacidade da máquina, pode existir apenas um slot compartilhado entre vários servidores ou vários slots permitindo que diferentes servidores sejam executados ao mesmo tempo.

Assim, o Server-Hub busca combinar:

* **Facilidade para os usuários**, abstraindo a infraestrutura e tornando simples a criação e inicialização de servidores.
* **Controle para o administrador**, permitindo definir os recursos e limites disponíveis na plataforma.
* **Gerenciamento compartilhado**, evitando que todo o grupo dependa de uma única pessoa para iniciar ou administrar um servidor.
* **Auto-hospedagem**, mantendo a infraestrutura sob controle do próprio grupo, seja em hardware próprio ou em uma VPS.

---

## Guia de instalação e primeiros passos

> Guia rápido para levar uma instalação nova do Server-Hub até um ambiente funcional.

### 1. Pré-requisitos

Antes de instalar o Server-Hub, você precisará de:

* Um servidor Linux com **Kubernetes (K8s)** ou **K3s** configurado.
* `kubectl` configurado e com acesso ao cluster.
* Um domínio que será utilizado para acessar a aplicação.
* Uma forma de expor a aplicação para a internet, caso deseje acesso externo.
* Armazenamento disponível no cluster para os PersistentVolumeClaims utilizados pela plataforma.

O Server-Hub pode ser executado tanto em **Kubernetes** quanto em **K3s**.

Para instalações menores, servidores domésticos ou máquinas que não fazem parte de um cluster maior, o **K3s é recomendado**, pois é mais leve, simples de instalar e suficiente para executar o Server-Hub.

O Kubernetes tradicional pode fazer mais sentido caso você:

* Já possua uma infraestrutura Kubernetes funcionando.
* Pretenda integrar o Server-Hub a um cluster existente.
* Esteja utilizando uma infraestrutura maior ou superdimensionada.

> [!TIP]
> Para a maioria das instalações domésticas e pequenos servidores, utilize **K3s**.

---

### 2. Baixar o Server-Hub

Não é necessário baixar manualmente a imagem Docker do Server-Hub.

A imagem oficial fica disponível no **GitHub Container Registry (GHCR)** e será baixada automaticamente pelo Kubernetes quando os Pods forem criados.

O primeiro passo é acessar a versão desejada na página de **Releases** do projeto e baixar o artifact:

```text
k3s-declaratives
```

Esse pacote contém uma estrutura de declarativos Kubernetes praticamente pronta para utilização.

A estrutura será semelhante a:

```text
k8s/
├── infrastructure/
├── platform/
│   ├── laravel/
│   ├── mariadb/
│   └── ...
└── ...
```

Você pode criar seus próprios declarativos Kubernetes caso prefira. Entretanto, existem configurações específicas necessárias para o funcionamento correto do Server-Hub, portanto é recomendado utilizar os declarativos fornecidos como base ou pelo menos consultá-los antes de criar os seus próprios.

Nos arquivos fornecidos, valores que precisam ser alterados estarão normalmente indicados entre:

```text
< >
```

Esses valores devem ser substituídos pelas configurações do seu ambiente.

---

### 3. Configurar o ambiente

#### 3.1. Configurar o MariaDB

Abra:

```text
k8s/platform/mariadb/mariadb.yaml
```

Localize:

```yaml
"<your_db_password>"
```

Apague o conteúdo entre `< >` e coloque entre aspas a senha que será utilizada pelo usuário do Laravel no banco.

Exemplo:

```yaml
"uma-senha-forte-aqui"
```

Essa senha deverá ser utilizada novamente posteriormente na configuração do Laravel.

##### Utilizando outro banco de dados

Caso você já possua uma instância MariaDB ou outro banco SQL compatível com Laravel, não é necessário utilizar o MariaDB fornecido.

Nesse caso, você pode remover:

```text
k8s/platform/mariadb/
```

Você deverá, entretanto, criar manualmente:

* O banco de dados do Server-Hub.
* Um usuário para o Laravel.
* Uma senha para esse usuário.
* As permissões necessárias para esse usuário acessar o banco.

Depois será necessário informar esses dados nas variáveis de ambiente do Laravel.

#### 3.2. Service de acesso ao MariaDB pelo host

Dentro da pasta do MariaDB também existe:

```text
k8s/platform/mariadb/mariadb_host_access.yaml
```

Esse arquivo cria um Service que facilita o acesso ao banco a partir do host.

Ele pode ser útil caso você queira utilizar ferramentas como:

* MySQL Workbench
* DBeaver
* Outros clientes SQL

Entretanto, expor o banco desnecessariamente aumenta a superfície de ataque da instalação.

> [!WARNING]
> Caso você não precise acessar diretamente o MariaDB pelo host, **remova o arquivo `mariadb_host_access.yaml` antes da instalação**.
>
> Se decidir utilizá-lo, é sua responsabilidade garantir que o banco e a rede estejam configurados de maneira segura.

#### 3.3. Configurar os Secrets do Laravel

Abra:

```text
k8s/platform/laravel/secrets.yaml
```

Os dois valores presentes nesse arquivo devem ser configurados obrigatoriamente.

##### `DB_PASSWORD`

O valor de:

```text
DB_PASSWORD
```

deve ser exatamente a mesma senha configurada anteriormente no MariaDB.

Caso você esteja utilizando um banco externo, utilize a senha do usuário criado para o Laravel.

##### `APP_KEY`

A `APP_KEY` é utilizada pelo Laravel para operações criptográficas e deve ser gerada corretamente.

Uma opção é clonar o repositório do Server-Hub e executar:

```bash
php artisan key:generate --show
```

Uma alternativa mais simples, sem precisar clonar o projeto, é:

```bash
echo "base64:$(openssl rand -base64 32)"
```

O resultado será semelhante a:

```text
base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

Copie o valor completo e utilize como valor de:

```text
APP_KEY
```

> [!WARNING]
> Não reutilize uma `APP_KEY` de outra instalação e não utilize os valores de exemplo da documentação.

#### 3.4. Configurar o Ingress

Abra:

```text
k8s/platform/laravel/laravel_ingress.yaml
```

Nesse arquivo existe o campo:

```yaml
host:
```

Ele deve receber o domínio utilizado para acessar o Server-Hub.

Informe somente o domínio, sem protocolo ou caminho.

Exemplo:

```yaml
host: serverhub.seudominio.com
```

Não utilize:

```text
https://serverhub.seudominio.com/
```

Como mencionado nas recomendações de segurança do projeto, é fortemente recomendado disponibilizar a aplicação atrás de um serviço como **Cloudflare Tunnel** ou solução equivalente.

O Cloudflare Tunnel é também a configuração utilizada como exemplo pelos declarativos atuais.

#### 3.5. Configurar as variáveis de ambiente do Laravel

Abra:

```text
k8s/platform/laravel/laravel_env.yaml
```

Esse arquivo contém a maior parte das variáveis de ambiente da aplicação.

Caso esteja utilizando o MariaDB fornecido pelos declarativos e tenha alterado somente a senha conforme instruído anteriormente, as configurações de banco existentes podem ser mantidas.

Caso esteja utilizando:

* Outro banco de dados.
* Outro nome de banco.
* Outro usuário.
* Outro hostname.

configure as respectivas variáveis de banco nesse arquivo.

O valor mais importante a ser alterado nessa etapa é:

```env
APP_URL=https://<your.domain>
```

Substitua somente:

```text
<your.domain>
```

pelo domínio configurado anteriormente.

Exemplo:

```env
APP_URL=https://serverhub.seudominio.com
```

> [!WARNING]
> **Não remova o `https://` da `APP_URL`.**
>
> O Server-Hub foi pensado para ser disponibilizado através de HTTPS, e utilizar HTTP publicamente não é recomendado.

---

### 4. Preparar o Kubernetes / K3s

#### 4.1. Configurar o Cloudflare Tunnel

Esta etapa é necessária somente caso você esteja utilizando **Cloudflare Tunnel**, que é a configuração recomendada atualmente.

Abra:

```text
k8s/infrastructure/cloudflared_secret.yaml
```

Localize:

```text
TUNNEL_TOKEN
```

Acesse o painel da Cloudflare, abra o Tunnel utilizado pelo Server-Hub e copie o token correspondente.

Insira esse token somente nesse Secret.

> [!WARNING]
> O `TUNNEL_TOKEN` é uma credencial privada.
>
> Alguém com acesso a esse token pode utilizar as credenciais do seu Tunnel. Não publique esse arquivo, não faça commit do token no Git e evite armazená-lo em locais desnecessários.
>
> Caso precise trocar a credencial posteriormente, prefira gerar um novo token e atualizar o Secret.

#### 4.2. Criar a rota do Tunnel

Dentro da configuração do Cloudflare Tunnel, crie uma rota para o Server-Hub.

O hostname deve ser **exatamente o mesmo domínio configurado anteriormente** no Ingress e na `APP_URL`.

Por exemplo:

```text
serverhub.seudominio.com
```

Se você estiver utilizando um subdomínio, configure exatamente esse mesmo subdomínio.

O campo **Path deve permanecer vazio**.

No campo **Service URL**, utilize:

```text
http://traefik.kube-system.svc.cluster.local:80
```

Esse endereço aponta para o Traefik dentro do cluster Kubernetes/K3s, que então encaminhará a requisição através do Ingress para o Laravel.

A configuração será, portanto, semelhante a:

```text
Hostname:
serverhub.seudominio.com

Path:
<deixar vazio>

Service URL:
http://traefik.kube-system.svc.cluster.local:80
```

---

### 5. Instalar o Server-Hub

Depois de revisar todos os valores indicados por `< >`, entre no diretório que contém a pasta `k8s` e execute:

```bash
kubectl apply -R -f k8s/
```

O Kubernetes/K3s começará a criar os recursos necessários, incluindo os Deployments, Services, ConfigMaps, Secrets, PersistentVolumeClaims e demais componentes da plataforma.

A imagem do Server-Hub será baixada automaticamente do GitHub Container Registry pelo runtime do Kubernetes.

Aguarde até que os principais Pods estejam em execução.

Em seguida, entre no container do Laravel:

```bash
kubectl exec -it deployment/laravel -n server-hub -- bash
```

Dentro do container, execute as migrations:

```bash
php artisan migrate
```

Depois gere os caches de produção do Laravel:

```bash
php artisan optimize
```

---

### 6. Verificar a instalação

Você pode verificar os recursos do Server-Hub com:

```bash
kubectl get pods -n server-hub
```

Também é útil verificar os Services:

```bash
kubectl get services -n server-hub
```

E o Ingress:

```bash
kubectl get ingress -n server-hub
```

Os principais Pods devem permanecer em estado semelhante a:

```text
Running
```

Caso algum Pod apresente problemas, utilize:

```bash
kubectl describe pod <pod> -n server-hub
```

ou:

```bash
kubectl logs <pod> -n server-hub
```

para investigar o erro.

---

### 7. Primeiro acesso

Antes de acessar a interface pela primeira vez, é necessário criar o primeiro usuário administrador.

Ainda dentro do container do Laravel, execute:

```bash
php artisan tinker
```

No Tinker, crie o usuário:

```php
$user = User::create([
    'name' => 'Admin',
    'email' => 'admin@serverhub.com',
    'password' => 'sua senha de admin',
    'is_admin' => true
]);
```

Substitua:

```text
sua senha de admin
```

por uma senha forte escolhida por você.

Depois saia do Tinker:

```text
exit
```

E saia do container:

```text
exit
```

Se todas as etapas anteriores estiverem corretas, o Server-Hub já deverá estar acessível através do domínio configurado anteriormente.

Acesse:

```text
https://serverhub.seudominio.com
```

e faça login utilizando o usuário administrador criado.

No primeiro acesso, as principais tarefas do administrador são:

1. Criar os usuários que poderão utilizar a plataforma.
2. Configurar as versões de Minecraft disponíveis, quando necessário.
3. Criar pelo menos um **Execution Slot** através do painel administrativo.

Cada Execution Slot representa a capacidade de manter **um servidor de jogo em execução simultaneamente**.

> [!WARNING]
> Crie somente a quantidade de Execution Slots que o hardware realmente consegue suportar.
>
> Criar 10 slots, por exemplo, significa permitir potencialmente que 10 servidores sejam executados ao mesmo tempo. A quantidade adequada dependerá principalmente da CPU e memória disponíveis no host.

Depois de criar pelo menos um Execution Slot, o ambiente básico do Server-Hub está pronto e seus usuários já podem começar a criar e executar servidores para jogar.

---

### Como acessar seu servidor criado no Server-Hub

> [!IMPORTANT]
> O Server-Hub utiliza **Execution Slots** para definir quais portas externas podem ser usadas pelos servidores de jogos. Por padrão, os slots começam na porta `30000` do host do cluster e são incrementados de um em um:
>
> * Slot `1` → porta `30000`
> * Slot `2` → porta `30001`
> * Slot `3` → porta `30002`
> * E assim por diante.
>
> Esses slots e suas respectivas portas podem ser visualizados e gerenciados pelo administrador da plataforma.

Quando um usuário inicia um servidor, o Server-Hub aloca automaticamente um dos slots disponíveis e fornece um endereço para conexão.

Por padrão, esse endereço é gerado a partir do mesmo domínio utilizado para acessar o Server-Hub, adicionando o prefixo `sv` seguido pelo número do slot.

Por exemplo, considerando que o Server-Hub esteja disponível em:

```text
your.domain
```

um servidor utilizando o **slot 1** será apresentado aos usuários como:

```text
sv1.your.domain
```

Enquanto um servidor utilizando o **slot 2** será apresentado como:

```text
sv2.your.domain
```

> [!NOTE]
> Se existir apenas um Execution Slot, o endereço de conexão será sempre o mesmo. Caso existam vários slots, um servidor pode receber um slot diferente cada vez que for iniciado e, consequentemente, seu endereço de conexão também pode mudar.

### Configuração de DNS

Para que esses endereços funcionem, o administrador deve criar previamente os registros DNS correspondentes aos Execution Slots existentes.

Por exemplo, caso existam três slots:

```text
sv1.your.domain
sv2.your.domain
sv3.your.domain
```

Cada endereço deve possuir um registro DNS do tipo `A` ou `AAAA`, dependendo da rede utilizada:

```text
sv1.your.domain  -> IP do servidor
sv2.your.domain  -> IP do servidor
sv3.your.domain  -> IP do servidor
```

* Use um registro `A` para IPv4.
* Use um registro `AAAA` para IPv6.

Todos eles normalmente apontarão para o mesmo endereço IP: o endereço pelo qual o host que executa os servidores de jogos pode ser alcançado pelos jogadores.

Esse endereço pode ser, por exemplo, o IP público da máquina ou um endereço acessível através de alguma infraestrutura de rede intermediária. Independentemente da solução utilizada, siga as recomendações descritas em [Avisos e Recomendações de Segurança](#avisos-e-recomendações-de-segurança).

### Registros SRV do Minecraft

Além dos registros `A` ou `AAAA`, é recomendado criar um registro `SRV` correspondente para cada slot.

O registro SRV permite que jogadores utilizem apenas:

```text
sv1.your.domain
```

em vez de precisarem informar manualmente:

```text
sv1.your.domain:30000
```

Para o **slot 1**, por exemplo, o registro deve utilizar:

```text
Service:  _minecraft
Protocol: _tcp
Name:     sv1
Port:     30000
Target:   sv1.your.domain
```

Para o **slot 2**:

```text
Service:  _minecraft
Protocol: _tcp
Name:     sv2
Port:     30001
Target:   sv2.your.domain
```

Seguindo o mesmo padrão para os demais slots.

Na prática, os registros resultantes serão equivalentes a:

```text
_minecraft._tcp.sv1.your.domain -> sv1.your.domain:30000
_minecraft._tcp.sv2.your.domain -> sv2.your.domain:30001
_minecraft._tcp.sv3.your.domain -> sv3.your.domain:30002
```

> [!TIP]
> O administrador ainda pode fornecer diretamente o endereço IP e a porta para conexão, por exemplo:
>
> ```text
> 203.0.113.10:30000
> ```
>
> Entretanto, utilizar os subdomínios `svN` juntamente com registros SRV deixa o endereço mais simples para os usuários e evita a necessidade de informar a porta manualmente.

### Cloudflare e proxies HTTP

> [!WARNING]
> Caso o domínio utilize **Cloudflare** ou outro provedor que ofereça proxy HTTP/HTTPS, o proxy deve permanecer **desativado nos registros utilizados pelos servidores de jogos**.
>
> O tráfego de um servidor Minecraft não é tráfego HTTP/HTTPS convencional e, portanto, não pode ser encaminhado através de serviços como Cloudflare Tunnel da mesma forma que o painel web do Server-Hub.

No caso da Cloudflare, os registros como:

```text
sv1.your.domain
sv2.your.domain
sv3.your.domain
```

devem permanecer como **DNS only** (nuvem cinza), apontando diretamente para o endereço pelo qual os servidores Minecraft podem ser alcançados.

Isso é independente da configuração utilizada para o painel web. Por exemplo:

```text
your.domain
    ↓
Cloudflare Tunnel
    ↓
Traefik
    ↓
Server-Hub
```

Enquanto os servidores de jogos seguem um caminho separado:

```text
sv1.your.domain
    ↓
DNS
    ↓
IP do host
    ↓
Porta 30000
    ↓
Servidor Minecraft
```

Essa separação permite que o painel web continue protegido e servido através de HTTPS, enquanto o tráfego dos servidores de jogos utiliza diretamente as portas destinadas aos Execution Slots.


---

### Mas se recomendamos K3s, por que a pasta se chama `k8s`?

Talvez você tenha percebido uma pequena inconsistência extremamente importante para o futuro da humanidade:

> **Se o K3s é recomendado para a maioria das instalações, por que a pasta dos declarativos se chama `k8s`?**

A resposta técnica e cuidadosamente arquitetada é: **porque o VS Code coloca o ícone bonitinho de Kubernetes quando a pasta se chama `k8s`.** 😅

**o ícone fica certo no VS Code.**

Algumas decisões de arquitetura são baseadas em escalabilidade, segurança e manutenibilidade.

Outras são baseadas em **estética no explorador de arquivos**.

Esta foi uma delas.

---

## Avisos e recomendações de segurança

> Informações importantes para quem pretende executar o Server-Hub, principalmente em ambientes acessíveis pela internet.

### Credenciais e Secrets

* Nunca armazene credenciais, tokens, senhas, chaves privadas ou outros dados sensíveis diretamente no código-fonte, imagens Docker, ConfigMaps ou arquivos versionados no repositório.

* Utilize **Kubernetes Secrets** ou outro mecanismo apropriado de gerenciamento de segredos para informações sensíveis da aplicação, banco de dados e serviços externos.

* Revise cuidadosamente suas configurações antes de disponibilizar a plataforma publicamente e utilize credenciais fortes e exclusivas para cada serviço.

### Kubernetes

* O uso de **Kubernetes ou K3s é recomendado**, pois esta é a arquitetura que será regularmente validada e testada antes das atualizações do projeto.

* Essa arquitetura não é obrigatória. A imagem Docker publicada pode ser utilizada para montar outro ambiente, porém instalações fora do Kubernetes **não são necessariamente testadas ou documentadas pelo projeto**.

* Mantenha o cluster atualizado, aplique o princípio do menor privilégio às permissões RBAC e exponha somente os recursos e portas realmente necessários.

### Rede e exposição pública

* Para ambientes acessíveis pela internet, é fortemente recomendado manter a aplicação web atrás de um túnel, como o **Cloudflare Tunnel**, ou serviço equivalente. Sempre prefira **HTTPS** para o tráfego externo e evite aceitar conexões HTTP não criptografadas provenientes da internet.

* Quando utilizar um provedor de túnel, habilite os mecanismos de segurança disponíveis, como políticas de acesso, controles de autenticação e filtragem de requisições. Também é recomendado configurar um **Web Application Firewall (WAF)**. A configuração de WAF não é abordada nesta documentação por estar fora do escopo do projeto.

* O servidor Minecraft representa um ponto de exposição diferente da aplicação web. O tráfego do jogo não pode ser protegido por um túnel HTTP da mesma maneira. Caso queira permitir conexões sem exigir VPN ou outro software dos jogadores, será necessário expor uma porta do host no firewall e no roteador.

* Na configuração padrão deste projeto, isso normalmente significa disponibilizar a porta `30000` para conexões Minecraft.

* Caso sua conexão esteja atrás de **CGNAT** ou o provedor bloqueie conexões de entrada, IPv6 pode ser utilizado como alternativa. Nesse cenário, o cluster Kubernetes ou K3s também deverá estar corretamente configurado para suportar IPv6.

* Expor diretamente a porta do Minecraft, seja por IPv4 ou IPv6, aumenta a superfície de ataque e deve ser feito somente quando o administrador compreender e aceitar os riscos envolvidos.

* Para uma configuração com menor exposição pública, prefira uma **VPN ou túnel peer-to-peer para o tráfego do jogo**. Essa abordagem é menos conveniente para os jogadores, mas evita disponibilizar diretamente o servidor Minecraft na internet.

* Não remova a exigência de **whitelist** de servidores Minecraft publicamente expostos sem compreender completamente as consequências. O projeto é open source e pode ser modificado, mas essa alteração fica sob responsabilidade de quem administra a instalação.

### Atualizações

* Mantenha o Server-Hub, as imagens utilizadas, o Kubernetes/K3s e o sistema operacional do host regularmente atualizados.

* A arquitetura Kubernetes/K3s documentada pelo projeto é a configuração recomendada porque será utilizada como referência nos testes antes da publicação de novas versões.

* Consulte as notas de cada release antes de atualizar, principalmente quando houver alterações relacionadas a manifests Kubernetes, variáveis de ambiente, banco de dados ou requisitos de infraestrutura.

> [!WARNING]
>
> Nenhum sistema conectado à internet é completamente impenetrável. O uso de HTTPS, túnel, WAF, firewall e Kubernetes reduz riscos, mas não elimina a necessidade de uma configuração segura por parte do administrador.
>
> Para uma configuração equilibrada, recomenda-se executar o Server-Hub em **Kubernetes ou K3s**, manter a aplicação web atrás de um túnel HTTPS, utilizar um WAF e expor o Minecraft somente quando os riscos dessa exposição forem aceitáveis.
>
> Se a prioridade for reduzir ao máximo a exposição pública, utilize uma **VPN ou túnel peer-to-peer também para o tráfego Minecraft**.
>
> A imagem Docker pode ser executada fora do Kubernetes, porém esse modo de instalação não é o caminho principal documentado ou regularmente testado pelo projeto.


---

## Versionamento

O Server-Hub utiliza um esquema próprio de versionamento no formato:

```text
X.Y.Z
```

Apesar de visualmente se parecer com o versionamento semântico tradicional, os três números possuem significados específicos dentro do projeto.

### Compatibilidade com jogos — `X`

O primeiro número **não representa uma versão principal ou grandes mudanças na plataforma**. Ele indica a expansão da compatibilidade do Server-Hub com novos jogos.

O projeto começa com:

```text
0.Y.Z
```

O índice `0` indica que, atualmente, existe apenas um jogo compatível com a plataforma: **Minecraft**.

Sempre que a compatibilidade com um novo jogo for adicionada, independentemente de qual jogo seja, esse número será incrementado.

Por exemplo:

```text
0.1.0 → Minecraft
1.1.0 → Minecraft + um novo jogo
2.1.0 → Minecraft + dois novos jogos
```

O primeiro número funciona de maneira **independente dos outros dois números da versão**.

Isso significa que adicionar suporte a um novo jogo não reinicia a progressão das versões de funcionalidades e correções.

Por exemplo:

```text
0.4.7
```

poderia passar para:

```text
1.4.7
```

caso fosse adicionada compatibilidade com um novo jogo.

Da mesma forma, a evolução dos outros dois números não depende do número de jogos suportados.

### Mudanças maiores e funcionalidades — `Y`

O segundo número segue uma lógica mais convencional e representa **mudanças maiores na plataforma**, novas funcionalidades importantes ou expansões significativas das funcionalidades existentes.

Durante o desenvolvimento inicial, o Server-Hub permanece em:

```text
0.0.Z
```

A primeira release pública planejada será:

```text
0.1.0
```

Essa primeira release ainda será considerada uma versão inicial do projeto e não necessariamente representa uma plataforma completamente estável ou finalizada.

Quando o Server-Hub atingir o conjunto de funcionalidades e o nível de estabilidade considerados mínimos para uma versão estável, esse número continuará evoluindo de acordo com as mudanças relevantes feitas no projeto.

### Correções e mudanças menores — `Z`

O último número representa mudanças menores, como:

* correções de bugs;
* pequenos ajustes de comportamento;
* melhorias menores de interface;
* ajustes internos;
* pequenas mudanças de configuração;
* outros fixes que não justificam uma nova versão de funcionalidades.

Por exemplo:

```text
0.1.0
0.1.1
0.1.2
0.1.3
```

### Exemplo atual

Uma versão como:

```text
0.0.1
```

significa:

* `0` — o Server-Hub possui atualmente compatibilidade apenas com servidores de Minecraft;
* `0` — o projeto ainda está na fase inicial, anterior à primeira release;
* `1` — primeira revisão ou conjunto de pequenas alterações dessa fase.

O ponto mais importante é que o primeiro número possui uma função diferente dos outros dois.

Por exemplo, se o projeto estiver na versão:

```text
0.3.5
```

e um novo jogo for adicionado, a versão poderá passar para:

```text
1.3.5
```

sem que `3` ou `5` sejam reiniciados.

---

## Arquitetura e tecnologias

> O Server-Hub usa uma stack hibrida de Laravel e Vue.js com divisão clara de responsabilidades onde toda parte de permissões, login, gerenciamento e conexões com o resto a aplicação é feito apenas no back end com Laravel e o Vue é usado apenas em paginas mais complexas com muita dinamicidade como por exemplo paginas de admin e gerenciamento de servidores, paginas mais simples como login e home são feitas inteiramente com o blade do Laravel.

### Aplicação

* **Backend:**`Laravel`
* **Frontend:**`Vue.js`
* **Banco de dados:**`SQL`
* **Cache / filas:**`Redis/SQL`

### Infraestrutura

* **Orquestração:**`Kubernetes`

---

## Jogos suportados

O Server-Hub está sendo desenvolvido inicialmente com foco em **Minecraft**. Outros jogos já estão planejados para versões futuras, mas ainda não possuem implementação na plataforma.

| Jogo              | Compatibilidade | Status                      |
| ----------------- | --------------: | --------------------------- |
| **Minecraft**     |         **60%** | 🚧 Em desenvolvimento       |
| **Terraria**      |          **0%** | 🕒 Futuramente implementado |
| **Assetto Corsa** |          **0%** | 🕒 Futuramente implementado |
| **Palworld**      |          **0%** | 🕒 Futuramente implementado |

> Os percentuais representam uma estimativa do nível atual de implementação da integração de cada jogo com o Server-Hub e podem mudar conforme o desenvolvimento do projeto avança.

---
