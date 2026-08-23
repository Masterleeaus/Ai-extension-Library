// ignore_for_file: avoid_function_literals_in_foreach_calls

import 'package:flutter/services.dart';

import '../../../backend/model/payment_link/payment_edit_link_model.dart';
import '../../../backend/model/payment_link/payment_update_model.dart';
import '../../../backend/model/payment_link/type_selection_drop_down.dart';
import '../../../backend/services/payment_link_services.dart';
import '../../../backend/utils/custom_snackbar.dart';
import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../views/categories/payments_screen/share_link/share_link_screen.dart';
import 'upload_image_controller/upload_image_controller.dart';

class PaymentsEditController extends GetxController
    with PaymentLinkApiServices {
  final imageController = Get.put(UploadImageController());
  final titleController = TextEditingController();
  final descriptionController = TextEditingController();
  final minimumAmountController = TextEditingController();
  final maximumAmountController = TextEditingController();
  final productsAndSubscriptionTitleController = TextEditingController();
  final amountController = TextEditingController();
  final quantityController = TextEditingController();
  final createNewLinkController = TextEditingController();
  final copyLinkController = TextEditingController();

  final RxString defaultImage = ''.obs;
  final RxString networkImage = ''.obs;
  final RxString amount = ''.obs;
  RxBool setLimit = false.obs;
  RxString typeSelection = ''.obs;
  RxString currencySelection = ''.obs;

  RxInt selectedLinkId = 0.obs;

  get onEditTap => Get.toNamed(Routes.paymentsEditScreen);

  final List<CurrencyDatum> currencyList = [];
  RxString currencyCode = ''.obs;
  RxString currencySymbol = ''.obs;
  RxString currencyCountry = ''.obs;
  RxString currencyName = ''.obs;
  List<TypeSelectionModel> typeSelectionList = [
    TypeSelectionModel(
      Strings.customerChoose,
    ),
    TypeSelectionModel(
      Strings.productsOrSubscriptions,
    ),
  ];

  @override
  onInit() {
    selectedLinkId.value = Get.arguments;
    if (selectedLinkId.value != 0) {
      getPaymentLinkEditProcess();
    }
    super.onInit();
  }

  //=> set loading process & payment link Model
  final _isEditLoading = false.obs;
  late PaymentLinkEditModel _paymentLinkEditModel;

  //=> get loading process & payment link Model
  bool get isEditLoading => _isEditLoading.value;

  PaymentLinkEditModel get paymentLinkEditModel => _paymentLinkEditModel;

  //=> get payment link edit api Process
  Future<PaymentLinkEditModel> getPaymentLinkEditProcess() async {
    _isEditLoading.value = true;
    update();
    await getPaymentEditLinkProcessApi(
      selectedLinkId.value,
    ).then((value) {
      _paymentLinkEditModel = value!;
      _isEditLoading.value = false;
      _setData(_paymentLinkEditModel);
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isEditLoading.value = false;
    update();
    return _paymentLinkEditModel;
  }

  _setData(PaymentLinkEditModel paymentLinkEdit) {
    defaultImage.value =
        "${_paymentLinkEditModel.data.baseUrl}/${_paymentLinkEditModel.data.defaultImage}";
    _paymentLinkEditModel.data.currencyData.forEach((element) {
      currencyList.add(CurrencyDatum(
        currencyName: element.currencyName,
        currencyCode: element.currencyCode,
        country: element.country,
        currencySymbol: element.currencySymbol,
      ));
    });
    var data = paymentLinkEdit.data.paymentLink;
    typeSelection.value = data.type == "pay"
        ? Strings.customerChoose
        : Strings.productsOrSubscriptions;
    titleController.text = data.title;
    productsAndSubscriptionTitleController.text = data.title;
    descriptionController.text = data.details;
    amountController.text = data.price;
    quantityController.text = data.qty.toString();
    setLimit.value = data.limit == 1 ? true : false;
    minimumAmountController.text = data.minAmount;
    maximumAmountController.text = data.maxAmount;
    currencyCode.value = data.currency;
    currencyCountry.value = data.country;
    currencyName.value = data.currencyName;
    currencySymbol.value = data.currencySymbol;
    currencySelection.value = '${data.currency}-${data.currencyName}';
    networkImage.value =
        '${paymentLinkEdit.data.baseUrl}/${paymentLinkEdit.data.imagePath}/${data.image}';
  }

  /// >> set loading process & Payment LinkStore Model
  final _isEditUpdateLoading = false.obs;
  late PaymentLinkUpdateModel _paymentLinkUpdateModel;

  /// >> get loading process & Payment LinkStore Model
  bool get isEditUpdateLoading => _isEditUpdateLoading.value;

  PaymentLinkUpdateModel get paymentLinkUpdateModel => _paymentLinkUpdateModel;

  /// >> Payment Link update  with image process api
  Future<PaymentLinkUpdateModel> paymentLinkUpdateWithImageProcess() async {
    _isEditLoading.value = true;
    update();
    Map<String, String> payInputBody = {
      'target': selectedLinkId.value.toString(),
      'currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'pay',
      'title': titleController.text,
      'details': descriptionController.text,
      'limit': setLimit.value == true ? 'on' : '',
      'min_amount': setLimit.value == true ? minimumAmountController.text : '',
      'max_amount': setLimit.value == true ? maximumAmountController.text : '',
      'image': imageController.userImagePath.value,
    };
    Map<String, String> subInputBody = {
      'target': selectedLinkId.value.toString(),
      'sub_currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'sub',
      'sub_title': productsAndSubscriptionTitleController.text,
      'price': amountController.text,
      'qty': quantityController.text,
    };

    await updatePaymentWithImageApi(
      body: typeSelection.value == Strings.customerChoose
          ? payInputBody
          : subInputBody,
      filepath: imageController.userImagePath.value,
    ).then((value) {
      _paymentLinkUpdateModel = value!;
      createNewLinkController.text =
          _paymentLinkUpdateModel.data.paymentLink.shareLink;
      _onEditCongratulationLink();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isEditUpdateLoading.value = false;
    update();
    return _paymentLinkUpdateModel;
  }

  /// >> Payment Link update  without image process api
  Future<PaymentLinkUpdateModel> paymentLinkUpdateWithOutImageProcess() async {
    _isEditUpdateLoading.value = true;
    update();

    Map<String, String> payInputBody = {
      'target': selectedLinkId.value.toString(),
      'currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'pay',
      'title': titleController.text,
      'details': descriptionController.text,
      'limit': setLimit.value == true ? 'on' : '',
      'min_amount': setLimit.value == true ? minimumAmountController.text : '',
      'max_amount': setLimit.value == true ? maximumAmountController.text : '',
    };
    Map<String, String> subInputBody = {
      'target': selectedLinkId.value.toString(),
      'sub_currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'sub',
      'sub_title': productsAndSubscriptionTitleController.text,
      'price': amountController.text,
      'qty': quantityController.text,
    };

    await updatePaymentWithoutImageApi(
      body: typeSelection.value == Strings.customerChoose
          ? payInputBody
          : subInputBody,
    ).then((value) {
      _paymentLinkUpdateModel = value!;
      createNewLinkController.text =
          _paymentLinkUpdateModel.data.paymentLink.shareLink;
      _onEditCongratulationLink();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isEditUpdateLoading.value = false;
    update();
    return _paymentLinkUpdateModel;
  }

  _onEditCongratulationLink() {
    Get.to(
      () => ShareLinkScreen(
        title: Strings.paymentLinkCreatedSuccessfully.tr,
        controller: createNewLinkController,
        btnName: Strings.createAnotherLink.tr,
        onTap: () async {
          await Clipboard.setData(
            ClipboardData(text: createNewLinkController.text),
          );
          CustomSnackBar.success(Strings.linkEditedSuccessfully.tr);
        },
        onButtonTap: () {
          Get.offAllNamed(Routes.paymentLogScreen);
        },
      ),
    );
  }
}
