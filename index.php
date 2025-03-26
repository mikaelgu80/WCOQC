<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <style>
    ins {
      background-color: #c6ffc6;
      text-decoration: none;
    }

    del {
      background-color: #ffc6c6;
    }

    ::file-selector-button {
      display: none;
    }

    main {
      padding-bottom: 50px;
      /* Adjust to match your footer's height */
    }

    .fade.show {
      opacity: 1 !important;
    }

    .modal.show .modal-dialog {
      -webkit-transform: translate(0, 0) !important;
      -o-transform: translate(0, 0) !important;
      transform: translate(0, 0) !important;
    }

    .modal-backdrop .fade .in {
      opacity: 0.5 !important;
    }

    .modal-backdrop.fade {
      opacity: 0.5 !important;
    }
  </style>
</head>
<title>WCO DM QC testing tool</title>
<header class="navbar">
  <div class="col col-md-12">
    <div class="pull-right">
      <span type="button" class="btn glyphicon glyphicon-cog" data-bs-toggle="modal" data-bs-target="#settingsModal"></span>
      <span type="button" class="btn glyphicon glyphicon-info-sign" data-bs-toggle="modal" data-bs-target="#infoModal"></span>
    </div>
  </div>
  <div class="upper">
    <div class="container">
      <h1>WCO DM QC testing tool</h1>
      <p>This tool is designed to aid in the qaulity control of the WCO Data model. It currently checks for consistent names, definitions and formats in the library and the overall information structure and between the two.</p>
    </div>
  </div>
</header>
<main>
  <div class="content-container">
    <div role="main">
      <div class="container">
        <div class="row">
          <div class="col col-md-4">
            <label for="library">Library spreadsheet</label>
            <div class="input-group">
              <input type="file" accept="application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="form-control" id="library" placeholder="Library spreadsheet">
              <span class="input-group-btn">
                <button class="btn btn-default" type="button"><span class="glyphicon glyphicon-file"></span></button>
              </span>
            </div>
          </div>
          <div class="col col-md-4">
            <label for="library">OIS spreadsheet</label>
            <div class="input-group">
              <input type="file" accept="application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="form-control" id="OIS" placeholder="OIS spreadsheet">
              <span class="input-group-btn">
                <button class="btn btn-default" type="button"><span class="glyphicon glyphicon-file"></span></button>
              </span>
            </div>
          </div>
          <div class="col col-md-3 pull-right">
            <button type="button" class="btn btn-primary pull-right" id="submitButton">Validate</button>
            <!--button type="button" class="btn btn-primary pull-right" id="spellButton">Spell check</button-->
          </div>
        </div>
        <div class="row">
          <div class="col col-md-12" id="output">
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<div class="modal fade" id="settingsModal" role="dialog">
  <?php
  $settingsFile = file_get_contents('php/settings.json');
  $settings = json_decode($settingsFile, true);
  $libcolumns = $settings['libcolumns'] ?? [];
  $oiscolumns = $settings['oiscolumns'] ?? [];
  $exceptions = $settings['exceptions'] ?? [];
  ?>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Settings</h4>
      </div>
      <div class="modal-body">
        <div>
          <ul class="nav nav-tabs" role="tablist" id="settingsnav">
            <li class="nav-item active">
              <a class="nav-link active" href="#paths" role="tab" data-bs-toggle="tab" aria-expanded="true">Column definitions</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#exceptions" role="tab" data-bs-toggle="tab" aria-expanded="true">Capitalization</a>
            </li>
          </ul>
        </div>
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane show active" id="paths">
            <div class="row mb-4">
              <div class="col col-md-12">
                <form action="php/save_settings.php" method="post" class="well">

                  <!-- LIBCOLUMNS SECTION -->
                  <div class="panel panel-primary">
                    <div class="panel-heading">Library columns</div>
                    <div class="panel-body">
                      <?php foreach ($libcolumns as $key => $value): ?>
                        <div class="form-group">
                          <label><?= htmlspecialchars($key) ?></label>
                          <input type="text" name="libcolumns[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= htmlspecialchars($value) ?>" required>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <!-- OISCOLUMNS SECTION -->
                  <div class="panel panel-success">
                    <div class="panel-heading">OIS columns</div>
                    <div class="panel-body">
                      <?php foreach ($oiscolumns as $key => $value): ?>
                        <div class="form-group">
                          <label><?= htmlspecialchars($key) ?></label>
                          <input type="text" name="oiscolumns[<?= htmlspecialchars($key) ?>]" class="form-control" value="<?= htmlspecialchars($value) ?>" required>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <button type="submit" class="btn btn-primary btn-block">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Save Settings
                  </button>

                </form>
              </div>
            </div>
          </div>
          <div role="tabpanel" class="tab-pane" id="exceptions">
            <div class="row mb-4">
              <div class="col col-md-12">
                <form action="php/save_exceptions.php" method="post" class="well">
                  <div id="exceptionsContainer">
                    <?php foreach ($exceptions as $exception): ?>
                      <div class="input-group exception-group">
                        <input type="text" name="exceptions[]" class="form-control" value="<?= htmlspecialchars($exception) ?>" required>
                        <span class="input-group-btn">
                          <button class="btn btn-danger" type="button" onclick="removeException(this)">Remove</button>
                        </span>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <button type="button" class="btn btn-success btn-block" onclick="addException()">
                    <span class="glyphicon glyphicon-plus"></span> Add Exception
                  </button>
                  <button type="submit" class="btn btn-primary btn-block">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Save
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="infoModal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Information about the QC helper tool</h4>
      </div>
      <div class="modal-body">
        <h5><u>Purpose</u></h5>
        <p>The purpose of this tool is to facilitate quality control of upcoming versions of the WCO Data model by programmatically checking for inconsistensies in the data.
        <h5><u>Functions</u></h5>
        <p>The data in the <b>library</b> are checked against various predefined constraints.</p>
        <ul>
          <li>Capitaliztion of class and attribute names.</li>
          <li>Naming and definitions of attributes based on their core data type.</li>
          <li>Definitions of classes.</li>
          <li>Punctiation of definitions.</li>
        </ul>
        <p>In addition separate instances of each data are cross checked for consistency. This is done both on the library and overall information structure levels. Should there be any inconsistensies as described above in the OIS, they will be brought to the users attention when data i cross checked between the library and OIS.</p>
        <p>When checking for inconsistensies, the last instance of each data in the library serves as the source. Should the same data have multiple occurrences with various discrepancies, these will be brought up by the data cross checks.</p>
        <h5><u>Spelling</u></h5>
        <p>Produces a Word document with IDs, names and definitions of the various data, with the purpose of facilitating the use of Word's spell checker.</p>
      </div>
    </div>
  </div>
</div>
<footer class="navbar-default navbar-fixed-bottom">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="col-md-8">
          <div class="version-environment">
            <p style="margin-top: 6px;">v1.3 / 10.3.2025
            </p>
          </div>
        </div>
        <div class="col-md-4" style="text-align:right;">
          <!--a href="#" class="btn btn-default btn-icon-right">Back to top <span class="icon icon-tulli-chevron-tight-up" aria-hidden="true"></span></a-->
        </div>
      </div>
      <hr>
      <div class="col-md-12">
        <div class="col-md-6">
        </div>
        <div class="col-md-6 legal">
          <!--span>© Tulli <?php echo date('Y'); ?>
              </span-->
        </div>
      </div>
    </div>
  </div>
</footer>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/diff_match_patch.js"></script>
<script src="js/jquery.pretty-text-diff.min.js"></script>
<script>
  $('#submitButton').on('click', function() {
    let formData = new FormData();
    const lib = $('#library').prop('files');
    const OIS = $('#OIS').prop('files');
    formData.append('operation', 'QC');
    formData.append('library', lib[0], lib[0].name);
    formData.append('OIS', OIS[0], OIS[0].name);

    let dots = 0;
    let interval = setInterval(function() {
      let dotsText = ".".repeat(dots % 4); // Creates a cycle: "", ".", "..", "..."
      $('#output').html("Processing" + dotsText);
      dots++;
    }, 500); // Updates every 500ms

    $.ajax({
      type: 'POST',
      url: 'php/wco.php',
      data: formData,
      contentType: false,
      cache: false,
      processData: false,
      success: function(data) {
        clearInterval(interval); // Stop the animation
        $('#output').html(data); // Display the actual result
        $('ol').prettyTextDiff({
          cleanup: true,
          diffContainer: ".changed:last"
        });
      },
      error: function() {
        clearInterval(interval);
        $('#output').html("An error occurred.");
      }
    });
  });
  /*$('#spellButton').on('click', function() {
    let formData = new FormData();
    const lib = $('#library').prop('files');
    const OIS = $('#OIS').prop('files');
    formData.append('operation', 'spellcheck');
    formData.append('library', lib[0], lib[0].name);
    formData.append('OIS', OIS[0], OIS[0].name);

    let dots = 0;
    let interval = setInterval(function() {
      let dotsText = ".".repeat(dots % 4); // Creates a cycle: "", ".", "..", "..."
      $('#output').html("Processing" + dotsText);
      dots++;
    }, 500); // Updates every 500ms

    $.ajax({
      type: 'POST',
      url: 'php/wco.php',
      data: formData,
      contentType: false,
      cache: false,
      processData: false,
      success: function(data) {
        clearInterval(interval); // Stop the animation
        $('#output').html('Document ready');
        var excelFileURL = 'php/' + data;

        var downloadLink = document.createElement('a');
        downloadLink.href = excelFileURL;
        //var now = moment().format('YYYY-MM-DD_H.mm');
        downloadLink.download = 'SpellCheck.docx'; // Specify the desired file name

        // Trigger the download
        document.body.appendChild(downloadLink);
        downloadLink.click();

        // Clean up the <a> element
        document.body.removeChild(downloadLink);
      },
      error: function() {
        clearInterval(interval);
        $('#output').html("An error occurred.");
      }
    });
  });*/

  function addException() {
    let container = document.getElementById("exceptionsContainer");
    let newInput = document.createElement("div");
    newInput.className = "input-group exception-group";
    newInput.innerHTML = `
                <input type="text" name="exceptions[]" class="form-control" placeholder="Enter exception" required>
                <span class="input-group-btn">
                    <button class="btn btn-danger" type="button" onclick="removeException(this)">Remove</button>
                </span>
            `;
    container.appendChild(newInput);
  }

  function removeException(button) {
    button.closest(".exception-group").remove();
  }
</script>
</body>