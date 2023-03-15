<!--
<div id="proposedChanges">
    <h2>V1 - Administration <small>(AL Recht)</small></h2>
    <div class="holder">
        <div>
            <div style="padding: 10px; clear:both;">
                <div style="display: inline-block; width: 200px;">
                    Sachgebiet:
                </div>
                <div style="display: inline-block; width: 400px;">
                    <select size="1" class="dbwv_select">
                        <option>S: Satzung</option>
                    </select>
                </div><br>

                <div style="display: inline-block; width: 200px; padding-top: 7px;">
                    Antragsnummer:
                </div>
                <div style="display: inline-block; width: 400px; padding-top: 7px;">
                    <input type="text" value="S1" class="form-control">
                </div><br>

                <div style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 7px;">
                    Sofort veröffentlichen:
                </div>
                <div style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 7px;">
                    <input type="checkbox">
                </div><br>
            </div>
            <div style="text-align: right;">
                <button type="submit" class="btn btn-default">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                    V2 erstellen mit Änderung
                </button>
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    V2 erstellen ohne Änderung
                </button>
            </div>
        </div>
    </div>
</div>
-->


<div id="proposedChanges">
    <h2>V2 - Administration <small>(AL Recht)</small></h2>
    <div>
        <div style="padding: 10px; clear:both;">
            <div style="display: inline-block; width: 200px;">
                Sachgebiet:
            </div>
            <div style="display: inline-block; width: 400px;">
                <select size="1" class="dbwv_select">
                    <option>S: Satzung</option>
                </select>
            </div><br>

            <div style="display: inline-block; width: 200px; padding-top: 7px;">
                Antragsnummer:
            </div>
            <div style="display: inline-block; width: 400px; padding-top: 7px;">
                <input type="text" value="S1" class="form-control">
            </div><br>

            <div style="display: inline-block; width: 200px; height: 40px; vertical-align: middle; padding-top: 7px;">
                Sofort veröffentlichen:
            </div>
            <div style="display: inline-block; width: 400px; height: 40px; vertical-align: middle; padding-top: 7px;">
                <input type="checkbox">
            </div><br>
        </div>
    </div>
    <div class="holder">
        <div class="statusForm">
            <button type="button" class="btn btn-default">
                <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                Text bearbeiten
            </button>
        </div>
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                Veröffentlichen
            </button>
        </div>
    </div>
</div>


<!--
<div id="proposedChanges">
    <h2>V2 - Administration <small>(Arbeitsgruppe)</small></h2>
    <div class="holder">
        <fieldset class="statusForm">
            <legend class="hidden">Vorgeschlagener Status</legend>
            <h3>Vorgeschlagener Status</h3>

            <label class="proposalStatus4">
                <input type="radio" name="proposalStatus" value="4"> Übernahme </label><br>
            <label class="proposalStatus10">
                <input type="radio" name="proposalStatus" value="10"> Modifizierte Übernahme<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Vorgeschlagene Änderungen werden im nächsten Bildschirm angezeigt)
            </label><br>

            <label class="proposalStatus5">
                <input type="radio" name="proposalStatus" value="5"> Ablehnung </label><br>
            <label class="proposalStatus10">
                <input type="radio" name="proposalStatus" value="10"> Überweisung </label><br>
            <label class="proposalStatus11">
                <input type="radio" name="proposalStatus" value="11"> Abstimmung </label><br>
            <label class="proposalStatus22">
                <input type="radio" name="proposalStatus" value="22"> Erledigt durch anderen ÄA </label><br>
            <label class="proposalStatus23">
                <input type="radio" name="proposalStatus" value="23"> Sonstiger Status </label><br>
            <label>
                <input type="radio" name="proposalStatus" value="0" checked=""> - nicht festgelegt -
            </label>
        </fieldset>

        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V3 erstellen
            </button>
        </div>
    </div>
</div>
-->

<!--
<div id="proposedChanges">
    <h2>V3 - Administration <small>(Redaktionsausschuss)</small></h2>
    <div class="holder">
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V4 Beschluss erstellen
            </button><br><br>
            (Dieser Button kann auch auf die Antragsliste für einen schnelleren Zugriff und führt zur Beschlusserstellungs-Seite)
        </div>
    </div>
</div>
-->

<!--
<div id="proposedChanges">
    <h2>V4 - Administration <small>(Koordinierungsausschuss)</small></h2>
    <div class="holder">
        <div style="text-align: right; padding: 10px;">
            <button type="submit" class="btn btn-default">
                <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                V5 (BV) mit Änderung erstellen
            </button>
            <button type="submit" class="btn btn-primary">
                <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                V5 (BV) ohne Änderung erstellen
            </button>
        </div>
    </div>
</div>
-->

<script>$(function() {
    $(".dbwv_select").selectize()
})</script>

