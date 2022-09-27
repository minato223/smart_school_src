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
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <form id='form1' action="<?php echo site_url('admin/presence') ?>" method="post" accept-charset="utf-8">
                        <div class="box-body">
                            <?php
                            if ($this->session->flashdata('msg')) {

                                echo $this->session->flashdata('msg');
                            }
                            ?>
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <h2 class="box-title"><i class="fa fa-users"></i> Liste de présence des professeurs</h2>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                    <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category">La catégorie d'enseignant</label>
                                        <select  id="category" name="category" class="form-control" >
                                            <?php
                                            foreach ($prof_categories as $key => $value) {
                                                ?>
                                                <option value="<?php echo $key ?>" <?php
                                                if ($current_category == $key) {
                                                    echo "selected =selected";
                                                }
                                                ?>><?php echo $value ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('role'); ?></span>
                                    </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="exampleInputEmail1">
                                                    Réchercher par date
                                                </label>
                                                <input name="date" placeholder="" type="text" class="form-control date" value="<?php echo set_value('date', date($this->customlib->getSchoolDateFormat())); ?>" readonly="readonly" />
                                                <span class="text-danger"><?php echo form_error('date'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button type="submit" name="search" value="search" class="btn btn-primary btn-sm pull-right checkbox-toggle"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <?php if (count($presences) <= 0) { ?>
                <div class="alert alert-info">Aucun enrégistrement touvé</div>
            <?php } else { ?>
                <div class="box box-primary">
                    <div class="box-header with-border">
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
                    </div>
                </div>

            <?php } ?>
        </div>
    </section>
</div>
</section>
</div>