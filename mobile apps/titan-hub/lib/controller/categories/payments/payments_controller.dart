// ignore_for_file: avoid_function_literals_in_foreach_calls

import 'package:flutter/services.dart';
import 'package:qrpay/backend/model/payment_link/type_selection_drop_down.dart';
import 'package:qrpay/controller/categories/payments/upload_image_controller/upload_image_controller.dart';
import 'package:qrpay/controller/profile/update_kyc_controller.dart';

import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/model/payment_link/payment_link_model.dart';
import '../../../backend/model/payment_link/payment_link_store_model.dart';
import '../../../backend/services/payment_link_services.dart';
import '../../../backend/utils/custom_snackbar.dart';
import '../../../routes/routes.dart';
import '../../../utils/basic_screen_imports.dart';
import '../../../views/categories/payments_screen/share_link/share_link_screen.dart';

class PaymentsController extends GetxController with PaymentLinkApiServices {
  final imageController = Get.put(UploadImageController());
  final kycVerificationController = Get.put(UpdateKycController());
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
  final RxString paymentImage = ''.obs;
  final RxString amount = ''.obs;
  RxBool setLimit = false.obs;
  RxString typeSelection = ''.obs;
  RxString currencySelection = ''.obs;
  RxInt selectedLinkId = 0.obs;

  get onPayments => Get.toNamed(Routes.paymentsScreen);

  onCreateNewLink() {
    if (kycVerificationController.kycModelData.data.kycStatus == 0) {
      CustomSnackBar.error(Strings.kycUnverifiedText.tr);
    } else if (kycVerificationController.kycModelData.data.kycStatus == 2) {
      CustomSnackBar.error(Strings.kycPendingText.tr);
    } else if (kycVerificationController.kycModelData.data.kycStatus == 3) {
      CustomSnackBar.error(Strings.kycRejectedText.tr);
    } else {
      imageController.isImagePathSet.value
          ? paymentLinkStoreWithImageProcess()
          : paymentLinkStoreWithOutImageProcess();
    }
  }

  get onCopyTap => _onCopyTap();

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
    super.onInit();
    getPaymentLinkProcess();
  }

  //=> set loading process & payment link Model
  final _isLoading = false.obs;
  late PaymentLinkModel _paymentLinkModel;

  //=> get loading process & payment link Model
  bool get isLoading => _isLoading.value;

  PaymentLinkModel get paymentLinkModel => _paymentLinkModel;

  //=> get payment link api Process
  Future<PaymentLinkModel> getPaymentLinkProcess() async {
    _isLoading.value = true;
    update();
    await getPaymentLinkProcessApi().then((value) {
      _paymentLinkModel = value!;
      _setData(_paymentLinkModel);
      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isLoading.value = false;
    update();
    return _paymentLinkModel;
  }

  _setData(PaymentLinkModel paymentLinkModel) {
    defaultImage.value =
        "${_paymentLinkModel.data.baseUrl}/${_paymentLinkModel.data.defaultImage}";
    _paymentLinkModel.data.currencyData.forEach((element) {
      currencyList.add(CurrencyDatum(
        currencyName: element.currencyName,
        currencyCode: element.currencyCode,
        country: element.country,
        currencySymbol: element.currencySymbol,
      ));
      currencySelection.value = currencyList.first.currencyCode +
          // ignore: prefer_interpolation_to_compose_strings
          '-' +
          currencyList.first.currencyName!;
      currencyCode.value = currencyList.first.currencyCode;
      currencySymbol.value = currencyList.first.currencySymbol;
      currencyCountry.value = currencyList.first.country;
      currencyName.value = currencyList.first.currencyName!;
    });
  }

  ///=> set loading process and  payment link Model
  final _isStatusLoading = false.obs;
  late CommonSuccessModel _commonSuccessModel;

  ///=> get loading process and  payment link Model
  bool get isStatusLoading => _isStatusLoading.value;

  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  Future<CommonSuccessModel> updateStatusProcess({required dynamic id}) async {
    _isStatusLoading.value = true;
    update();
    Map<String, dynamic> inputBody = {
      'target': id,
    };
    await updatePaymentLinkStatusApi(body: inputBody).then((value) {
      _commonSuccessModel = value!;
      _isStatusLoading.value = false;
      getPaymentLinkProcess();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isStatusLoading.value = false;
    update();
    return _commonSuccessModel;
  }

  /// >> set loading process & Payment LinkStore Model
  final _isUpdateLoading = false.obs;
  late PaymentLinkStoreModel _paymentLinkStoreModel;

  /// >> get loading process & Payment LinkStore Model
  bool get isUpdateLoading => _isUpdateLoading.value;

  PaymentLinkStoreModel get paymentLinkStoreModel => _paymentLinkStoreModel;

  Future<PaymentLinkStoreModel> paymentLinkStoreWithImageProcess() async {
    _isUpdateLoading.value = true;
    update();

    Map<String, String> payInputBody = {
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
      'sub_currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'sub',
      'sub_title': productsAndSubscriptionTitleController.text,
      'price': amountController.text,
      'qty': quantityController.text,
    };

    await paymentLinkStoreWithImageApi(
      body: typeSelection.value == Strings.customerChoose
          ? payInputBody
          : subInputBody,
      filepath: imageController.userImagePath.value,
    ).then((value) {
      _paymentLinkStoreModel = value!;
      createNewLinkController.text =
          _paymentLinkStoreModel.data.paymentLink.shareLink;
      _onCreateNewLink();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isUpdateLoading.value = false;
    update();
    return _paymentLinkStoreModel;
  }

  //without image
  Future<PaymentLinkStoreModel> paymentLinkStoreWithOutImageProcess() async {
    _isUpdateLoading.value = true;
    update();

    Map<String, String> payInputBody = {
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
      'sub_currency': currencyCode.value,
      'currency_name': currencyName.value,
      'currency_symbol': currencySymbol.value,
      'country': currencyCountry.value,
      'type': 'sub',
      'sub_title': productsAndSubscriptionTitleController.text,
      'price': amountController.text,
      'qty': quantityController.text,
    };

    await paymentLinkStoreWithoutImageApi(
      body: typeSelection.value == Strings.customerChoose
          ? payInputBody
          : subInputBody,
    ).then((value) {
      _paymentLinkStoreModel = value!;
      createNewLinkController.text =
          _paymentLinkStoreModel.data.paymentLink.shareLink;
      _onCreateNewLink();
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isUpdateLoading.value = false;
    update();
    return _paymentLinkStoreModel;
  }

  _onCreateNewLink() {
    Get.to(
      () => ShareLinkScreen(
        title: Strings.paymentLinkCreatedSuccessfully.tr,
        controller: createNewLinkController,
        btnName: Strings.createAnotherLink.tr,
        onTap: () async {
          await Clipboard.setData(
            ClipboardData(text: createNewLinkController.text),
          );
          CustomSnackBar.success(Strings.linkCopiedSuccessfully.tr);
        },
        onButtonTap: () {
          Get.offAllNamed(Routes.paymentLogScreen);
        },
      ),
    );
  }

  _onCopyTap() async {
    await Clipboard.setData(
      ClipboardData(text: copyLinkController.text),
    );
    CustomSnackBar.success(Strings.linkCopiedSuccessfully.tr);
  }
}
