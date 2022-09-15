<style>
    .retard {
        display: inline-block;
        height: 15px;
        width: 15px;
        background: red;
        border-radius: 50%;
    }

    .retard.non {
        background: greenyellow;
    }
</style>
<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);
?>
<div class="content-wrapper" style="min-height: 946px;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><i class="fa fa-sitemap"></i> <?php echo $this->lang->line('human_resource'); ?></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <h3>Presence des élèves</h3>
        <div class="row">
            <?php if (count($presences) <= 0) { ?>
                <div class="alert alert-info">Aucun enrégistrement touvé</div>
            <?php } else { ?>
                <table class="table">
                    <thead>
                        <th>Utilisateur</th>
                        <th>Heure d'Arrivée</th>
                        <th>Retard</th>
                    </thead>
                    <tbody>
                        <?php foreach ($presences as $presence) { ?>
                            <tr>
                                <td><?php echo $presence->getUser()->getName() . " " . $presence->getUser()->getSurname() ?></td>
                                <td><?php echo explode(" ", $presence->getFormattedAuthDateTime())[1] ?></td>
                                <td><span class="retard <?php echo $presence->getRetard() == 1 ? 'oui' : 'non' ?>"></span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

        </div>

    </section>
</div>