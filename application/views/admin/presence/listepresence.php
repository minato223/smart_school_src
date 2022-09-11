<style>
.retard {
    display: inline-block;
    height: 15px;
    width: 15px;
    background: red;
    border-radius: 50%;
}
.retard.non{
    background: greenyellow;
}
</style>
<?php
ini_set("display_errors","1");
error_reporting(E_ALL);
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
}
for ($i=0; $i < 20; $i++) {
    $heure = (new DateTimeImmutable("now"))->setTime(random_int(7,9),random_int(0,59));
    $morning = (new DateTimeImmutable("now"))->setTime(8,0);
    $users[]=[
        "id"=> $i+1,
        "prenom"=> generateRandomString(),
        "nom"=> generateRandomString(),
        "date"=> $heure->format('Y-m-d H:i:s'),
        "retard"=> $morning->getTimestamp() < $heure->getTimestamp(),
    ];
}
print_r($presences[0]);
?>
<div class="content-wrapper" style="min-height: 946px;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><i class="fa fa-sitemap"></i> <?php echo $this->lang->line('human_resource'); ?></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <table class="table">
                <thead>
                    <th>Utilisateur</th>
                    <th>Heure d'Arrivée</th>
                    <th>Retard</th>
                </thead>
                <tbody>
                    <?php foreach ($presences as $presence) { ?>
                    <tr>
                        <td><?php /*echo $presence->getSurname()." ".$presence->getName() */?></td>
                        <td><?php echo explode(" ",$presence->getFormattedAuthDateTime())[1] ?></td>
                        <td><span class="retard <?php echo $presence->getRetard()==1?'oui':'non' ?>"></span></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </section>
</div>