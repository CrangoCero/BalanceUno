$(document).ready(function () {
  // Obtener la fecha actual para configurar los datepickers
  const today = new Date();
  // Definir configuración i18n en una constante
  const es = {
    months: [
      "Enero",
      "Febrero",
      "Marzo",
      "Abril",
      "Mayo",
      "Junio",
      "Julio",
      "Agosto",
      "Septiembre",
      "Octubre",
      "Noviembre",
      "Diciembre",
    ],
    monthsShort: [
      "Ene",
      "Feb",
      "Mar",
      "Abr",
      "May",
      "Jun",
      "Jul",
      "Ago",
      "Sep",
      "Oct",
      "Nov",
      "Dic",
    ],
    weekdays: [
      "Domingo",
      "Lunes",
      "Martes",
      "Miércoles",
      "Jueves",
      "Viernes",
      "Sábado",
    ],
    weekdaysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
    weekdaysAbbrev: ["D", "L", "M", "M", "J", "V", "S"],
    cancel: "Cancelar",
    clear: "Limpiar",
    done: "Aceptar",
  };

  // Configuración base centralizada
  const baseConfig = {
    format: "dd/mm/yyyy",
    autoClose: true,
    todayHighlight: true,
    container: "body",
    i18n: es,
  };

  //inicializar selects
  $("select").formSelect({
    dropdownOptions: {
      coverTrigger: false,
      closeOnClick: true,
    },
  });

  // Opcional: inicializar tooltips de Materialize si los usas
  $(".tooltipped").tooltip();

  // Ajuste para botones en móviles
  $(".action-btn").addClass("waves-effect waves-light");

  // Incomes DataTable
  if (typeof $.fn.DataTable === "function" && $("#incomesTable").length) {
    $("#incomesTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip", // sin selector de cantidad de registros
      order: [[0, "desc"]], // ordenar por fecha descendente
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        {
          responsivePriority: 1,
          targets: 0,
          className: "dt-body-center",
        }, // Fecha
        {
          responsivePriority: 1,
          targets: "col-monto",
          className: "dt-body-right",
        },
        {
          responsivePriority: 3,
          targets: -1,
          orderable: false,
          searchable: false,
        }, // Acciones
      ],
    });
  }

  // Expenses DataTable
  if (typeof $.fn.DataTable === "function" && $("#expensesTable").length) {
    $("#expensesTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip", // sin selector de cantidad de registros
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        {
          responsivePriority: 1,
          targets: 0,
          className: "dt-body-center",
        }, // Fecha
        {
          responsivePriority: 2,
          targets: 2,
          className: "dt-body-right",
        }, // Monto
        {
          responsivePriority: 3,
          targets: -1,
          orderable: false,
          searchable: false,
        }, // Acciones
      ],
    });
  }

  // Loans Datatable
  if (typeof $.fn.DataTable === "function" && $("#loansTable").length) {
    $("#loansTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip", // sin selector de cantidad de registros
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        {
          responsivePriority: 1,
          targets: 0, // Préstamo
          className: "dt-body-left",
        },
        {
          responsivePriority: 2,
          targets: 1, // Monto
          className: "dt-body-right",
        },
        {
          responsivePriority: 2,
          targets: 2, // Saldo
          className: "dt-body-right",
        },
        {
          responsivePriority: 3,
          targets: -1, // Acciones
          orderable: false,
          searchable: false,
        },
      ],
    });
  }

  // Inventory DataTable
  if (typeof $.fn.DataTable === "function" && $("#inventoryTable").length) {
    $("#inventoryTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip",
      order: [[1, "asc"]],
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        { className: "dt-body-right", targets: [2, 3, 4, 5] },
        { className: "dt-body-center", targets: 0 },
        { orderable: false, targets: -1 },
      ],
    });
  }

  // Orders DataTable
  if (typeof $.fn.DataTable === "function" && $("#ordersTable").length) {
    $("#ordersTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip",
      order: [[0, "desc"]],
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        { className: "dt-body-right", targets: 3 },
        { className: "dt-body-center", targets: [0, 2, 4] },
        { orderable: false, targets: -1 },
      ],
    });
  }

  // Movements History DataTable
  let movementsHistoryTable;
  if (
    typeof $.fn.DataTable === "function" &&
    $("#movementsHistoryTable").length
  ) {
    movementsHistoryTable = $("#movementsHistoryTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 5,
      dom: "frtip",
      order: [[0, "desc"]],
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [{ className: "dt-body-center", targets: [0, 1, 2] }],
    });
  }
  let loanHistoryTable;
  if (typeof $.fn.DataTable === "function" && $("#loanHistoryTable").length) {
    loanHistoryTable = $("#loanHistoryTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 5,
      dom: "frtip",
      order: [[0, "desc"]],
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [{ className: "dt-body-right", targets: 3 }],
    });
  }

  // Balance DataTable
  if (typeof $.fn.DataTable === "function" && $("#balanceTable").length) {
    $("#balanceTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip",
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        {
          responsivePriority: 1,
          targets: 0, // Fecha
          className: "dt-body-center",
        },
        {
          responsivePriority: 2,
          targets: 3, // Monto
          className: "dt-body-right",
        },
      ],
    });
  }

  //Reports DataTable
  if (typeof $.fn.DataTable === "function" && $("#reportsTable").length) {
    $("#reportsTable").DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 10,
      dom: "frtip",
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
      },
      columnDefs: [
        { responsivePriority: 1, targets: 0 }, // Fecha
        { responsivePriority: 2, targets: 3, className: "dt-body-right" }, // Monto
      ],
    });
  }

  // Recalcular columnas al redimensionar (Fix para Materialize Tabs/Modals)
  $(window).on("resize", function () {
    if (typeof $.fn.DataTable === "function") {
      $(".dataTable").DataTable().columns.adjust().responsive.recalc();
    }
  });

  // Inicializar modales
  $(".modal").modal({
    onOpenStart: function (modal, trigger) {
      //evitar que los datos carguen al mismo tiempo que los labels, lo que hace que se oculten
      setTimeout(function () {
        M.updateTextFields();
      }, 100);

      if (modal.id === "modalEditExpense") {
        // Obtener datos del botón
        var id = $(trigger).data("id");
        var date = $(trigger).data("date");
        var description = $(trigger).data("description");
        var amount = $(trigger).data("amount");
        var payment_method = $(trigger).data("payment_method");
        var code = $(trigger).data("code");
        var category_id = $(trigger).data("category_id");
        var payment_status = $(trigger).data("payment_status");

        // Rellenar el formulario
        $('#modalEditExpense input[name="id"]').val(id);
        $('#modalEditExpense input[name="date"]').val(date);
        $('#modalEditExpense input[name="description"]').val(description);

        if (description.startsWith("Pago Préstamo")) {
          $('#modalEditExpense input[name="description"]').prop(
            "readonly",
            true,
          );
          $('#modalEditExpense input[name="description"]').addClass(
            "grey-text",
          );
        } else {
          $('#modalEditExpense input[name="description"]').prop(
            "readonly",
            false,
          );
          $('#modalEditExpense input[name="description"]').removeClass(
            "grey-text",
          );
        }

        $('#modalEditExpense input[name="amount"]').val(formatNumber(amount));

        // Categoría select
        $("#edit_expense_category_id option").prop("selected", false);
        $('#edit_expense_category_id option[value="' + category_id + '"]').prop(
          "selected",
          true,
        );
        $("#edit_expense_category_id").formSelect({
          dropdownOptions: { coverTrigger: false, closeOnClick: true },
        });

        // 👇 Truco: marcar el option correcto ANTES de refrescar
        $("#modal_payment_method option").prop("selected", false); // limpiar
        $('#modal_payment_method option[value="' + payment_method + '"]').prop(
          "selected",
          true,
        );

        // Refrescar Materialize
        $("#modal_payment_method").formSelect({
          dropdownOptions: {
            coverTrigger: false,
            closeOnClick: true,
          },
        });

        $('#modalEditExpense input[name="code"]').val(code);

        // Payment status select
        $("#edit_expense_payment_status option").prop("selected", false);
        $(
          '#edit_expense_payment_status option[value="' + payment_status + '"]',
        ).prop("selected", true);
        $("#edit_expense_payment_status").formSelect({
          dropdownOptions: { coverTrigger: false, closeOnClick: true },
        });
      } else if (modal.id === "modalEditIncome") {
        // Obtener datos del botón
        var id = $(trigger).data("id");
        var date = $(trigger).data("date");
        var description = $(trigger).data("description");
        var amount = $(trigger).data("amount");
        var payment_method = $(trigger).data("payment_method");
        var code = $(trigger).data("code");
        var category_id = $(trigger).data("category_id");
        var payment_status = $(trigger).data("payment_status");

        // Rellenar el formulario
        $('#modalEditIncome input[name="id"]').val(id);
        $('#modalEditIncome input[name="date"]').val(date);
        $('#modalEditIncome input[name="description"]').val(description);

        if (description.startsWith("Préstamo")) {
          $('#modalEditIncome input[name="description"]').prop(
            "readonly",
            true,
          );
          $('#modalEditIncome input[name="description"]').addClass("grey-text");

          // Cambiar estilos a "loans-color"
          $("#modalEditIncome h5")
            .removeClass("secondary-color-text")
            .addClass("loans-color-text");
          $("#modalEditIncome h5").html(
            '<i class="material-icons left">sync</i> Editar Préstamo',
          );
          $('#modalEditIncome button[type="submit"]')
            .removeClass("secondary-color")
            .addClass("loans-color");
        } else {
          $('#modalEditIncome input[name="description"]').prop(
            "readonly",
            false,
          );
          $('#modalEditIncome input[name="description"]').removeClass(
            "grey-text",
          );

          // Revertir a "secondary-color"
          $("#modalEditIncome h5")
            .removeClass("loans-color-text")
            .addClass("secondary-color-text");
          $("#modalEditIncome h5").html(
            '<i class="material-icons left">edit</i> Editar Ingreso',
          );
          $('#modalEditIncome button[type="submit"]')
            .removeClass("loans-color")
            .addClass("secondary-color");
        }

        $('#modalEditIncome input[name="amount"]').val(formatNumber(amount));

        // Categoría select
        $("#edit_income_category_id option").prop("selected", false);
        $('#edit_income_category_id option[value="' + category_id + '"]').prop(
          "selected",
          true,
        );
        $("#edit_income_category_id").formSelect({
          dropdownOptions: { coverTrigger: false, closeOnClick: true },
        });

        // 👇 Truco: marcar el option correcto ANTES de refrescar
        $("#modal_payment_method option").prop("selected", false); // limpiar
        $('#modal_payment_method option[value="' + payment_method + '"]').prop(
          "selected",
          true,
        );

        // Refrescar Materialize
        $("#modal_payment_method").formSelect({
          dropdownOptions: {
            coverTrigger: false,
            closeOnClick: true,
          },
        });

        $('#modalEditIncome input[name="code"]').val(code);

        // Payment status select
        $("#edit_income_payment_status option").prop("selected", false);
        $(
          '#edit_income_payment_status option[value="' + payment_status + '"]',
        ).prop("selected", true);
        $("#edit_income_payment_status").formSelect({
          dropdownOptions: { coverTrigger: false, closeOnClick: true },
        });
      } else if (modal.id === "modalEditLoanPayment") {
        // Obtener datos del botón
        var id = $(trigger).data("id");
        var date = $(trigger).data("date");
        var amount = $(trigger).data("amount");
        var payment_method = $(trigger).data("payment_method");
        var code = $(trigger).data("code");
        var loan_id = $(trigger).data("loan_id");

        // Rellenar el formulario
        $('#modalEditLoanPayment input[name="id"]').val(id);
        $('#modalEditLoanPayment input[name="date"]').val(date);
        $('#modalEditLoanPayment input[name="amount"]').val(
          formatNumber(amount),
        );

        $("#modal_edit_loan_payment_method option").prop("selected", false); // limpiar
        $(
          '#modal_edit_loan_payment_method option[value="' +
            payment_method +
            '"]',
        ).prop("selected", true);
        $("#modal_edit_loan_payment_method").formSelect({
          dropdownOptions: {
            coverTrigger: false,
            closeOnClick: true,
          },
        });

        $("#modal_edit_loan_id option").prop("selected", false); // limpiar
        $('#modal_edit_loan_id option[value="' + loan_id + '"]').prop(
          "selected",
          true,
        );
        $("#modal_edit_loan_id").formSelect({
          dropdownOptions: {
            coverTrigger: false,
            closeOnClick: true,
          },
        });

        $('#modalEditLoanPayment input[name="code"]').val(code);
      } else if (modal.id === "modalEditCategory") {
        var id = $(trigger).data("id");
        var name = $(trigger).data("name");
        var type = $(trigger).data("type");

        $('#modalEditCategory input[name="id"]').val(id);
        $('#modalEditCategory input[name="name"]').val(name);

        $("#edit_category_type option").prop("selected", false);
        $('#edit_category_type option[value="' + type + '"]').prop(
          "selected",
          true,
        );
        $("#edit_category_type").formSelect({
          dropdownOptions: { coverTrigger: false, closeOnClick: true },
        });
      } else if (modal.id === "modalEditProduct") {
        var id = $(trigger).data("id");
        var sku = $(trigger).data("sku");
        var name = $(trigger).data("name");
        var description = $(trigger).data("description");
        var min_stock = $(trigger).data("min_stock");
        var cost_price = $(trigger).data("cost_price");
        var sale_price = $(trigger).data("sale_price");

        $('#modalEditProduct input[name="id"]').val(id);
        $('#modalEditProduct input[name="sku"]').val(sku);
        $('#modalEditProduct input[name="name"]').val(name);
        $('#modalEditProduct textarea[name="description"]').val(description);
        $('#modalEditProduct input[name="min_stock"]').val(min_stock);
        $('#modalEditProduct input[name="cost_price"]').val(
          formatNumber(cost_price),
        );
        $('#modalEditProduct input[name="sale_price"]').val(
          formatNumber(sale_price),
        );
      }
    },
  });

  // Antes de enviar, copiar el valor del select al hidden
  $("#modalEditIncome .modal-content form").on("submit", function (e) {
    e.preventDefault();
    const val = $("#modal_payment_method").val();
    $("#payment_method_hidden").val(val);
    handleAjaxSubmit($(this));
  });

  // Antes de enviar, copiar el valor del select al hidden
  $("#modalEditExpense .modal-content form").on("submit", function (e) {
    e.preventDefault();
    const val = $("#modal_payment_method").val();
    $("#payment_method_hidden").val(val);
    handleAjaxSubmit($(this));
  });

  // Antes de enviar, copiar el valor del select al hidden para pago de préstamo
  $("#modalEditLoanPayment .modal-content form").on("submit", function (e) {
    e.preventDefault();
    const valMethod = $("#modal_edit_loan_payment_method").val();
    $("#edit_loan_payment_method_hidden").val(valMethod);
    const valLoanId = $("#modal_edit_loan_id").val();
    $("#edit_loan_id_hidden").val(valLoanId);
    handleAjaxSubmit($(this));
  });

  // Manejar todos los demás formularios de modales (crear, eliminar) de forma genérica
  $(".modal form")
    .not("#modalEditIncome form")
    .not("#modalEditExpense form")
    .not("#modalEditLoanPayment form")
    .not("#formCreateOrder") //
    .on("submit", function (e) {
      e.preventDefault();
      handleAjaxSubmit($(this));
    });

  // Load Loan History via AJAX
  $(document).on("click", ".btn-view-history", function (e) {
    e.preventDefault();
    const loanId = $(this).data("id");
    const loanName = $(this).data("loan");

    $("#historyLoanName").text(loanName);

    if (loanHistoryTable) {
      loanHistoryTable.clear().draw();
    }

    $.ajax({
      url: "?action=getLoanPayments&id=" + loanId,
      method: "GET",
      dataType: "json",
      success: function (res) {
        if (res.success && loanHistoryTable) {
          res.data.forEach(function (payment) {
            const dateParts = payment.date.split("-");
            const formattedDate =
              dateParts[2] + "/" + dateParts[1] + "/" + dateParts[0];
            const formattedAmount = "$" + formatNumber(payment.amount);
            const method =
              '<span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;">' +
              payment.payment_method +
              "</span>";
            const code =
              payment.payment_method === "Efectivo" ? "-" : payment.code || "";

            loanHistoryTable.row.add([
              formattedDate,
              method,
              code,
              '<span class="accent-color-text" style="font-weight:600;">' +
                formattedAmount +
                "</span>",
            ]);
          });
          loanHistoryTable.draw(false);
          setTimeout(function () {
            loanHistoryTable.columns.adjust().responsive.recalc();
          }, 200);
        } else {
          M.toast({
            html: res.message || "Error al procesar datos",
            classes: "error-color",
          });
        }
      },
      error: function () {
        M.toast({
          html: "Error al cargar el historial",
          classes: "error-color",
        });
      },
    });
  });

  function handleAjaxSubmit($form) {
    const url = $form.attr("action");
    const formData = $form.serialize();

    // Deshabilitar botón para evitar envíos múltiples por clic repetido
    const $submitBtn = $form.find('button[type="submit"]');
    $submitBtn.prop("disabled", true);

    // Mostrar cargando
    Swal.fire({
      title: "Procesando...",
      didOpen: () => {
        Swal.showLoading();
      },
      allowOutsideClick: false,
    });

    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (res) {
        if (res.success) {
          Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: res.message,
            timer: 2000,
            showConfirmButton: false,
          }).then(() => {
            location.reload();
          });
        } else {
          $submitBtn.prop("disabled", false); // Recomponer en caso de error
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message || "Ocurrió un error inesperado",
          });
        }
      },
      error: function (xhr) {
        $submitBtn.prop("disabled", false); // Recomponer en caso de error
        let errorMsg = "No se pudo comunicar con el servidor";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        Swal.fire({
          icon: "error",
          title: "Error",
          text: errorMsg,
        });
      },
    });
  }

  // Función para convertir dd/mm/yyyy a Date
  function parseDate(str) {
    if (!str) return null;
    const parts = str.split("/");
    return new Date(parts[2], parts[1] - 1, parts[0]);
  }

  // Inicializar TODOS los datepickers genéricos
  $(".datepicker").each(function () {
    const val = $(this).val();
    const defaultDate = val ? parseDate(val) : today;

    $(this).datepicker({
      ...baseConfig,
      maxDate: today,
      defaultDate: defaultDate,
      setDefaultDate: !!defaultDate,
    });
  });

  // Inicializar fecha inicial con lógica de rango
  $("#startDate").datepicker({
    ...baseConfig,
    maxDate: today,
    defaultDate: parseDate($("#startDate").val()) || today,
    setDefaultDate: true,
    onSelect: function (selectedDate) {
      const start = new Date(selectedDate);

      // Obtener instancia del endDate
      const endElem = document.getElementById("endDate");
      const endInstance = M.Datepicker.getInstance(endElem);

      if (endInstance) {
        endInstance.options.minDate = start; // actualizar restricción
        endInstance.gotoDate(start); // mover calendario a esa fecha
      }
    },
  });

  // Inicializar fecha final con lógica de rango
  $("#endDate").datepicker({
    ...baseConfig,
    maxDate: today,
    defaultDate: parseDate($("#endDate").val()) || today,
    setDefaultDate: true,
    onSelect: function (selectedDate) {
      const end = new Date(selectedDate);

      // Obtener instancia del startDate
      const startElem = document.getElementById("startDate");
      const startInstance = M.Datepicker.getInstance(startElem);

      if (startInstance) {
        startInstance.options.maxDate = end; // actualizar restricción
        startInstance.gotoDate(end); // mover calendario a esa fecha
      }
    },
  });

  // Gráfico de torta: ingresos y gastos separados por método de pago
  const cty = $("#reportChartPaymentMethod");
  if (cty.length && paymentSummary.length > 0) {
    const labels = paymentSummary.map(
      (item) => `${item.tipo} - ${item.payment_method}`,
    );
    const values = paymentSummary.map((item) => item.total);

    // Paleta de colores extendida
    const colors = [
      "#43a047",
      "#1e88e5",
      "#fb8c00",
      "#e53935",
      "#8e24aa",
      "#00acc1",
      "#fdd835",
      "#6d4c41",
    ];

    new Chart(cty, {
      type: "doughnut",
      data: {
        labels: labels,
        datasets: [
          {
            data: values,
            backgroundColor: colors.slice(0, labels.length),
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: "bottom" },
          title: {
            display: true,
            text: "Distribución por método de pago (Ingresos vs Gastos)",
          },
        },
      },
    });
  }

  // Inicializar Chart.js usando datos pasados desde PHP
  const ctx = $("#reportChart");
  if (ctx.length) {
    new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Ingresos", "Gastos"],
        datasets: [
          {
            data: [totalsData.incomes, totalsData.expenses],
            backgroundColor: ["#fb8c00", "#e53935"],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: "bottom" },
          title: { display: true, text: "Distribución General" },
        },
      },
    });
  }

  function formatNumber(val) {
    if (!val) return "";

    // Si el valor ya viene con separadores (ej. "58.000"), no lo parsees
    if (/^\d{1,3}(\.\d{3})*(,\d+)?$/.test(val)) {
      return val; // ya está formateado
    }

    // Si viene crudo (ej. "58000.00"), entonces sí lo parseas
    let num = parseFloat(val);
    if (isNaN(num)) return "";

    if (num % 1 === 0) {
      return Math.trunc(num).toLocaleString("es-CO", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
    }

    return num.toLocaleString("es-CO", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  }

  // Al escribir en cualquier campo amount
  $(document).on("input", 'input[name="amount"]', function () {
    console.log($(this).val());
    let raw = $(this).val().replace(/\D/g, ""); // solo dígitos
    if (raw) {
      const num = parseInt(raw, 10);
      $(this).val(formatNumber(num));
    }
  });

  // Load Product Movements History via AJAX
  $(document).on("click", ".btn-view-movements-history", function (e) {
    e.preventDefault();
    const productId = $(this).data("id");
    const productName = $(this).data("name");

    $("#movementsProductName").text(productName);

    if (movementsHistoryTable) {
      movementsHistoryTable.clear().draw();
    }

    $.ajax({
      url: "?action=getProductMovements&id=" + productId,
      method: "GET",
      dataType: "json",
      success: function (res) {
        if (res.success && movementsHistoryTable) {
          res.data.forEach(function (mov) {
            const dateObj = new Date(mov.created_at);
            const formattedDate =
              dateObj.toLocaleDateString("es-ES") +
              " " +
              dateObj.toLocaleTimeString("es-ES", {
                hour: "2-digit",
                minute: "2-digit",
              });

            const typeBadge =
              mov.type === "entrada"
                ? '<span class="badge green white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Entrada</span>'
                : '<span class="badge red white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Salida</span>';

            const reference = mov.reference_type
              ? mov.reference_type.toUpperCase() +
                (mov.reference_id ? " #" + mov.reference_id : "")
              : "-";

            movementsHistoryTable.row.add([
              formattedDate,
              typeBadge,
              mov.quantity,
              reference,
              htmlspecialchars(mov.notes || ""),
            ]);
          });
          movementsHistoryTable.draw(false);
          setTimeout(function () {
            movementsHistoryTable.columns.adjust().responsive.recalc();
          }, 200);
        } else {
          M.toast({
            html: res.message || "Error al procesar datos",
            classes: "error-color",
          });
        }
      },
      error: function () {
        M.toast({
          html: "Error al cargar el historial",
          classes: "error-color",
        });
      },
    });
  });

  // --- ORDERS FLOW LOGIC ---

  // Recalcular el total del pedido al crear
  function recalculateOrderTotals() {
    let subtotal = 0;
    $("#orderItemsContainer .item-row").each(function () {
      const priceText = $(this).find(".input-item-price").val() || "0";
      const price = parseFloat(priceText.replace(/\D/g, "")) || 0;
      const qty = parseInt($(this).find(".input-item-qty").val()) || 0;
      const rowSubtotal = price * qty;

      $(this).find(".label-item-subtotal").text(formatNumber(rowSubtotal));
      subtotal += rowSubtotal;
    });

    $("#orderSubtotalVal").text(formatNumber(subtotal));

    // Determinar tasa de impuesto
    let taxRate = 0;
    const taxSpan = $("#orderTaxVal");
    if (taxSpan.length) {
      const labelText = taxSpan.closest("span").text();
      const match = labelText.match(/IVA\s*\((\d+(\.\d+)?)\%\)/i);
      if (match) {
        taxRate = parseFloat(match[1]);
      }
    }

    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;

    if (taxSpan.length) {
      taxSpan.text(formatNumber(taxAmount));
    }
    $("#orderTotalVal").text(formatNumber(total));
  }

  // Escuchar cambios en productos del selector
  $(document).on("change", ".select-order-product", function () {
    const selectedOpt = $(this).find("option:selected");
    const price = selectedOpt.data("price") || 0;

    const row = $(this).closest(".item-row");
    row.find(".input-item-price").val(formatNumber(price));
    recalculateOrderTotals();
  });

  // Escuchar cambios en cantidad
  $(document).on("input change", ".input-item-qty", function () {
    recalculateOrderTotals();
  });

  // Agregar nueva fila de producto
  $("#btnAddNewOrderItem").on("click", function (e) {
    e.preventDefault();

    // Clonar la primera fila
    const firstRow = $("#orderItemsContainer .item-row").first();
    const newRow = firstRow.clone();

    // Resetear valores en el clon
    newRow.find("select").val("");
    newRow.find(".input-item-price").val("");
    newRow.find(".input-item-qty").val("1");
    newRow.find(".label-item-subtotal").text("0");

    $("#orderItemsContainer").append(newRow);
  });

  // Eliminar fila de producto
  $(document).on("click", ".btn-remove-item", function (e) {
    e.preventDefault();
    if ($("#orderItemsContainer .item-row").length > 1) {
      $(this).closest(".item-row").remove();
      recalculateOrderTotals();
    } else {
      M.toast({
        html: "El pedido debe tener al menos un producto.",
        classes: "error-color",
      });
    }
  });

  // Enviar formulario de creación
  $("#formCreateOrder").on("submit", function (e) {
    e.preventDefault();
    let valid = true;
    $(".select-order-product").each(function () {
      if (!$(this).val()) {
        valid = false;
      }
    });
    if (!valid) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Por favor, selecciona un producto para cada fila.",
      });
      return;
    }
    handleAjaxSubmit($(this));
  });

  // Ver detalles del pedido mediante AJAX
  $(document).on("click", ".btn-view-order-details", function (e) {
    e.preventDefault();
    const orderId = $(this).data("id");

    $("#detailsItemsContainer").html("");
    $("#detailsNotesContainer").hide();

    $.ajax({
      url: "?action=getOrderItems&id=" + orderId,
      method: "GET",
      dataType: "json",
      success: function (res) {
        if (res.success) {
          const order = res.data;

          $("#detailsOrderNumber").text(order.order_number);
          $("#detailsCustomerName").text(order.customer_name);

          const dateObj = new Date(order.created_at);
          $("#detailsOrderDate").text(dateObj.toLocaleDateString("es-ES"));

          // Badge de estado
          const badge = $("#detailsOrderStatusBadge");
          badge.removeClass("grey blue green red");
          badge.text(order.status);
          if (order.status === "Borrador") badge.addClass("grey");
          else if (order.status === "Confirmado") badge.addClass("blue");
          else if (order.status === "Entregado") badge.addClass("green");
          else badge.addClass("red");

          // Llenar ítems
          order.items.forEach(function (item) {
            const formattedPrice = "$" + formatNumber(item.unit_price);
            const formattedSubtotal = "$" + formatNumber(item.subtotal);

            $("#detailsItemsContainer").append(`
              <tr>
                <td style="font-weight: 500;">${htmlspecialchars(item.product_name)} <span class="grey-text" style="font-size:0.8rem;">(${htmlspecialchars(item.sku || "")})</span></td>
                <td class="right-align">${formattedPrice}</td>
                <td class="center-align">${item.quantity}</td>
                <td class="right-align" style="font-weight:600;">${formattedSubtotal}</td>
              </tr>
            `);
          });

          // Notas
          if (order.notes) {
            $("#detailsNotesText").text(order.notes);
            $("#detailsNotesContainer").show();
          }

          // Totales
          $("#detailsSubtotalText").text("$" + formatNumber(order.subtotal));
          if ($("#detailsTaxText").length) {
            $("#detailsTaxText").text("$" + formatNumber(order.tax_amount));
          }
          $("#detailsTotalText").text("$" + formatNumber(order.total));
        } else {
          M.toast({
            html: res.message || "Error al cargar detalles",
            classes: "error-color",
          });
        }
      },
      error: function () {
        M.toast({
          html: "Error de comunicación con el servidor",
          classes: "error-color",
        });
      },
    });
  });

  // Cambio de estado de pedido (Confirmar, Entregar, Cancelar)
  $(document).on("click", ".btn-change-order-status", function (e) {
    e.preventDefault();
    const orderId = $(this).data("id");
    const newStatus = $(this).data("status");

    let confirmTitle = "¿Confirmar pedido?";
    let confirmText = "Esto descontará los productos del inventario.";
    let confirmBtn = "Confirmar";
    let confirmColor = "#2196f3";

    if (newStatus === "Entregado") {
      confirmTitle = "¿Marcar como Entregado?";
      confirmText =
        "Esto registrará la entrega y generará un ingreso financiero automático.";
      confirmBtn = "Marcar como Entregado";
      confirmColor = "#4caf50";
    } else if (newStatus === "Cancelado") {
      confirmTitle = "¿Cancelar pedido?";
      confirmText =
        "Esto cancelará el pedido y devolverá los productos al inventario si aplica.";
      confirmBtn = "Cancelar Pedido";
      confirmColor = "#f44336";
    } else if (newStatus === "Borrador") {
      confirmTitle = "¿Revertir a Borrador?";
      confirmText = "Esto regresará el pedido a borrador.";
      confirmBtn = "Revertir";
      confirmColor = "#9e9e9e";
    }

    Swal.fire({
      title: confirmTitle,
      text: confirmText,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: confirmColor,
      cancelButtonColor: "#9e9e9e",
      confirmButtonText: confirmBtn,
      cancelButtonText: "No, conservar",
    }).then((result) => {
      if (result.isConfirmed) {
        $("#changeStatusOrderId").val(orderId);
        $("#changeStatusOrderVal").val(newStatus);
        handleAjaxSubmit($("#formChangeOrderStatus"));
      }
    });
  });

  function htmlspecialchars(string) {
    if (!string) return "";
    return string
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
});
