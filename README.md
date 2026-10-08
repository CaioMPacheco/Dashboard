# Dashboard para empresas - Projeto estudantil sem objetivo de aplicação

Descrição
---------
Dashboard web para empresas, com interface em HTML/CSS/JS e backend em PHP. Este projeto fornece telas de gestão e visualização de dados para uso administrativo.

Principais tecnologias
----------------------
- PHP (backend)
- MySQL / MariaDB (provável persistência)
- HTML, CSS, JavaScript (frontend)

Recursos esperados
------------------
- Painéis de métricas e gráficos
- Autenticação básica (login)
- CRUD para entidades (usuários, clientes, produtos, etc.)
- Importação/exportação de dados

Instalação e execução
---------------------
1. Clone o repositório:
   git clone https://github.com/CaioMPacheco/Dashboard.git
2. Copie os arquivos para a raiz do servidor (ex.: `htdocs` do XAMPP) ou configure um Virtual Host.
3. Crie o banco de dados e importe quaisquer arquivos `.sql` presentes.
4. Atualize as credenciais de conexão ao banco nos arquivos PHP de configuração (procure em `php/` ou em arquivos config).
5. Acesse via navegador (ex.: `http://localhost/Dashboard/`).

Recomendações de segurança
--------------------------
- Não deixe credenciais no repositório; use arquivo de configuração não versionado ou variáveis de ambiente.
- Utilize prepared statements para queries e valide entradas do usuário.
- Ajuste permissões no servidor para não expor arquivos sensíveis.

Como contribuir
---------------
1. Abra uma issue com a descrição do problema/feature.
2. Crie branch: `git checkout -b fix/issue-descr`.
3. Faça PR com testes e instruções de validação.
