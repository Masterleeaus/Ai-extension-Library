import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/model/add_money/add_money_razorpay_insert_model.dart';

import '../../../backend/model/add_money/add_model_bkash_model.dart';
import '../../../backend/model/add_money/add_money_pagadito_insert_model.dart';
import '../../../backend/model/add_money/add_money_payment_gateway_model.dart';
import '../../../backend/model/add_money/add_money_paypal_insert_model.dart';
import '../../../backend/model/add_money/add_money_ssl_insert_model.dart';
import '../../../backend/model/add_money/add_money_stripe_insert_model.dart';
import '../../../backend/model/add_money/coingate_insert_model.dart';
import '../../../backend/model/add_money/send_money_manual_insert_model.dart';
import '../../../backend/model/add_money/tatum_gateway_model.dart';
import '../../../backend/model/categories/deposit/add_money_flutter_wave_model.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../language/english.dart';
import '../../../model/id_type_model.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../views/categories/add_money/add_money_web_bkash_screen.dart';
import '../../../views/categories/add_money/pay_stack_web_payment_screen.dart';
import '../../../widgets/inputs/manual_payment_image_widget.dart';
import '../../../widgets/inputs/primary_input_filed.dart';
import '../../../widgets/others/congratulation_widget.dart';
import '../../../widgets/payment_link/custom_drop_down.dart';
import '../../../widgets/text_labels/custom_title_heading_widget.dart';
import '../remaining_balance_controller/ramaining_controller.dart';

class DepositController extends GetxController {
  final copyInputController = TextEditingController();

  List<TextEditingController> inputFieldControllers = [];
  RxList inputFields = [].obs;
  List<String> listImagePath = [];
  List<String> listFieldName = [];
  RxBool hasFile = false.obs;

  final selectedIDType = "".obs;
  List<IdTypeModel> idTypeList = [];

  RxString baseCurrency = "".obs;
  RxString selectedCurrencyAlias = "".obs;
  RxString selectedCurrencyName = "Select Method".obs;
  RxString selectedCurrencyType = "".obs;
  RxString selectedGatewaySlug = "".obs;
  RxString currencyCode = "".obs;
  RxString currencyWalletCode = "".obs;
  RxString gatewayImage = "".obs;
  RxString gatewayTrx = "".obs;
  RxInt selectedCurrencyId = 0.obs;
  RxInt crypto = 0.obs;
  RxDouble fee = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble dailyLimit = 0.0.obs;
  RxDouble monthlyLimit = 0.0.obs;
  String enteredAmount = "";
  String transferFeeAmount = "";
  String totalCharge = "";
  String youWillGet = "";
  String payableAmount = "";

  // Tatum qrcode address
  RxString qrAddress = "".obs;

  List<Currency> currencyList = [];
  List<String> baseCurrencyList = [];

  final depositMethod = TextEditingController();

  final cardNumberController = TextEditingController();
  final cardExpiryController = TextEditingController();
  final cardCVCController = TextEditingController();

  final cardHolderNameController = TextEditingController();
  final cardExpiryDateController = TextEditingController();
  final cardCvvController = TextEditingController();
  final remainingController = Get.put(RemaingBalanceController());

  void gotoPreview() {
    Get.toNamed(Routes.depositPreviewScreen);
  }

  @override
  void onInit() {
    getAddMoneyPaymentGatewayData();
    amountTextController.text = '0.0';
    super.onInit();
  }

  // ---------------------------- AddMoneyPaymentGatewayModel ------------------
  // api loading process indicator variable
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late AddMoneyPaymentGatewayModel _addMoneyPaymentGatewayModel;

  AddMoneyPaymentGatewayModel get addMoneyPaymentGatewayModel =>
      _addMoneyPaymentGatewayModel;

  // --------------------------- Api function ----------------------------------
  // get addMoneyPaymentGateway data function
  Future<AddMoneyPaymentGatewayModel> getAddMoneyPaymentGatewayData() async {
    _isLoading.value = true;
    update();

    await ApiServices.addMoneyPaymentGatewayAPi().then((value) {
      _addMoneyPaymentGatewayModel = value!;

      currencyCode.value =
          _addMoneyPaymentGatewayModel.data.userWallet.currency;
      currencyWalletCode.value = _addMoneyPaymentGatewayModel
          .data.gateways.first.currencies.first.currencyCode;

      for (var gateways in _addMoneyPaymentGatewayModel.data.gateways) {
        for (var currency in gateways.currencies) {
          currencyList.add(
            Currency(
              id: currency.id,
              paymentGatewayId: currency.paymentGatewayId,
              crypto: currency.crypto,
              name: currency.name,
              alias: currency.alias,
              currencyCode: currency.currencyCode,
              currencySymbol: currency.currencySymbol,
              minLimit: currency.minLimit,
              maxLimit: currency.maxLimit,
              dailyLimit: currency.dailyLimit,
              monthlyLimit: currency.monthlyLimit,
              percentCharge: currency.percentCharge,
              fixedCharge: currency.fixedCharge,
              rate: currency.rate,
              createdAt: currency.createdAt,
              updatedAt: currency.updatedAt,
              type: currency.type,
              image: currency.image,
            ),
          );
        }
      }

      Currency currency =
          _addMoneyPaymentGatewayModel.data.gateways.first.currencies.first;
      Gateway gateway = _addMoneyPaymentGatewayModel.data.gateways.first;

      selectedCurrencyAlias.value = currency.alias;
      selectedCurrencyType.value = currency.type;
      selectedGatewaySlug.value = gateway.slug;
      selectedCurrencyId.value = currency.id;
      selectedCurrencyName.value = currency.name;
      crypto.value = currency.crypto;
      //start remaing get
      remainingController.transactionType.value =
          _addMoneyPaymentGatewayModel.data.getRemainingFields.transactionType;
      remainingController.attribute.value =
          _addMoneyPaymentGatewayModel.data.getRemainingFields.attribute;
      remainingController.cardId.value =
          _addMoneyPaymentGatewayModel.data.gateways.first.currencies.first.id;
      remainingController.senderAmount.value = amountTextController.text;
      remainingController.senderCurrency.value = _addMoneyPaymentGatewayModel
          .data.gateways.first.currencies.first.currencyCode;

      remainingController.getRemainingBalanceProcess();
      rate.value = double.parse(currency.rate);

      fee.value = double.parse(currency.fixedCharge);
      limitMin.value = double.parse(currency.minLimit) / rate.value;
      limitMax.value = double.parse(currency.maxLimit) / rate.value;
      dailyLimit.value = double.parse(currency.dailyLimit) / rate.value;
      monthlyLimit.value = double.parse(currency.monthlyLimit) / rate.value;
      percentCharge.value = double.parse(currency.percentCharge);

      //Base Currency
      baseCurrency.value = _addMoneyPaymentGatewayModel.data.baseCurr;
      baseCurrencyList.add(baseCurrency.value);

      _isLoading.value = false;
      update();
      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
      update();
    });

    return _addMoneyPaymentGatewayModel;
  }

  // ---------------------------- AddMoneyPaypalInsertModel --------------------

  final _isInsertLoading = false.obs;

  bool get isInsertLoading => _isInsertLoading.value;

  late AddMoneyPaypalInsertModel _addMoneyInsertPaypalModel;

  AddMoneyPaypalInsertModel get addMoneyInsertPaypalModel =>
      _addMoneyInsertPaypalModel;

  // --------------------------- Api function ----------------------------------
  // add money paypal
  Future<AddMoneyPaypalInsertModel> addMoneyPaypalInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.sendMoneyInsertPaypalApi(body: inputBody).then((value) {
      _addMoneyInsertPaypalModel = value!;
      final data = _addMoneyInsertPaypalModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyInsertPaypalModel;
  }

  late AddMoneyFlutterWavePaymentModel _addMoneyInsertFlutterWaveModel;

  AddMoneyFlutterWavePaymentModel get addMoneyInsertFlutterWaveModel =>
      _addMoneyInsertFlutterWaveModel;

  // --------------------------- Api function ----------------------------------
  // add money paypal
  Future<AddMoneyFlutterWavePaymentModel>
      addMoneyFlutterWaveInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.sendMoneyInsertFlutterWaveApi(body: inputBody)
        .then((value) {
      _addMoneyInsertFlutterWaveModel = value!;
      final data = _addMoneyInsertFlutterWaveModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyInsertFlutterWaveModel;
  }

  /// add money ssl insert process
  late AddMoneySslInsertModel _addMoneySslInsertModel;

  AddMoneySslInsertModel get addMoneySslInsertModel => _addMoneySslInsertModel;

  // --------------------------- Api function ----------------------------------
  // add money ssl process
  Future<AddMoneySslInsertModel> addMoneySslInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneySslProcessApi(body: inputBody).then((value) {
      _addMoneySslInsertModel = value!;
      final data = _addMoneySslInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneySslInsertModel;
  }

  /// add money coinGate insert process
  late CoinGateInsertModel _addMoneyCoinGateInsertModel;

  CoinGateInsertModel get addMoneyCoinGateInsertModel =>
      _addMoneyCoinGateInsertModel;

  // --------------------------- Api function ----------------------------------
  // add money coinGate process
  Future<CoinGateInsertModel> addMoneyCoinGateInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyCoinGateProcessApi(body: inputBody).then((value) {
      _addMoneyCoinGateInsertModel = value!;
      final data = _addMoneyCoinGateInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();

      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyCoinGateInsertModel;
  }

  /// >>> Add Money RazorPay Insert Process
  late AddMoneyRazorPayInsertModel _addMoneyRazorPayInsertModel;

  AddMoneyRazorPayInsertModel get addMoneyRazorPayInsertModel =>
      _addMoneyRazorPayInsertModel;

  // --------------------------- Api function ----------------------------------
  // add money RazorPay
  Future<AddMoneyRazorPayInsertModel> addMoneyRazorPayInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertRazorPayApi(body: inputBody).then((value) {
      _addMoneyRazorPayInsertModel = value!;
      final data = _addMoneyRazorPayInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyRazorPayInsertModel;
  }

  /// >>> Add Money Perfect Money Insert Process
  late AddMoneyRazorPayInsertModel _addMoneyPerfectMoneyInsertModel;

  AddMoneyRazorPayInsertModel get addMoneyPerfectMoneyInsertModel =>
      _addMoneyPerfectMoneyInsertModel;

  // --------------------------- Api function ----------------------------------
  // add money Perfect Money
  Future<AddMoneyRazorPayInsertModel>
      addMoneyPerfectMoneyInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertRazorPayApi(body: inputBody).then((value) {
      _addMoneyPerfectMoneyInsertModel = value!;
      final data = _addMoneyPerfectMoneyInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyPerfectMoneyInsertModel;
  }

  // ---------------------------- AddMoneyStripeInsertModel --------------------
  late AddMoneyStripeInsertModel _addMoneyInsertStripeModel;

  AddMoneyStripeInsertModel get addMoneyInsertStripeModel =>
      _addMoneyInsertStripeModel;

  // --------------------------- Api function ----------------------------------
  // add money stripe
  Future<AddMoneyStripeInsertModel> addMoneyStripeInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertStripeApi(body: inputBody).then((value) {
      _addMoneyInsertStripeModel = value!;
      final data = _addMoneyInsertStripeModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyInsertStripeModel;
  }

// bkash payment gateway
  late AddMoneyBkashInsertModel _addMoneyBkashInsertModel;

  AddMoneyBkashInsertModel get addMoneyBkashInsertModel =>
      _addMoneyBkashInsertModel;
  Future<AddMoneyBkashInsertModel> addMoneyBkashProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertBkashApi(body: inputBody).then((value) {
      _addMoneyBkashInsertModel = value!;
      final data = _addMoneyBkashInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyBkashInsertModel;
  }

  /// >>> Add Money Pagadito Insert Process
  late AddMoneyPagaditoInsertModel _addMoneyPagaditoInsertModel;
  AddMoneyPagaditoInsertModel get addMoneyPagaditoInsertModel =>
      _addMoneyPagaditoInsertModel;
  // --------------------------- Api function ----------------------------------
  // add money Pagadito
  Future<AddMoneyPagaditoInsertModel> addMoneyPagaditoInsertProcess() async {
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertPagaditoApi(body: inputBody).then((value) {
      _addMoneyPagaditoInsertModel = value!;
      final data = _addMoneyPagaditoInsertModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _addMoneyPagaditoInsertModel;
  }

  // ---------------------------- addMoneyStripeConfirmProcess -----------------
  final _isConfirmLoading = false.obs;

  bool get isConfirmLoading => _isConfirmLoading.value;

  late CommonSuccessModel _addMoneyStripeConfirmModel;

  CommonSuccessModel get addMoneyStripeConfirmModel =>
      _addMoneyStripeConfirmModel;

  // --------------------------- Api function ----------------------------------
  // send money stripe
  Future<CommonSuccessModel> addMoneyStripeConfirmProcess() async {
    _isConfirmLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'track': addMoneyInsertStripeModel.data.paymentInformations.trx,
      'name': cardHolderNameController.text,
      'cardNumber': cardNumberController.text,
      'cardExpiry':
          '${cardExpiryDateController.text.split('/')[0]}/${cardExpiryDateController.text.split('/')[1]}',
      'cardCVC': cardCvvController.text,
    };

    await ApiServices.addMoneyStripeConfirmApi(body: inputBody).then((value) {
      _addMoneyStripeConfirmModel = value!;

      // Get.offAllNamed(Routes.bottomNavBarScreen);
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isConfirmLoading.value = false;
    update();
    return _addMoneyStripeConfirmModel;
  }

  goToWebPaymentViewScreen() {
    Get.toNamed(Routes.paypalWebPaymentScreen);
  }

  goToWebFlutterWavePaymentViewScreen() {
    Get.toNamed(Routes.flutterWaveWebPaymentScreen);
  }

  goToStripeScreen() {
    Get.toNamed(Routes.stripeWebPaymentScreen);
  }

  goToCoinGateScreen() {
    Get.toNamed(Routes.coinGatePaymentScreen);
  }

  goToPerfectMoneyScreen() {
    Get.toNamed(Routes.perfectMoneyWebPaymentScreen);
  }

  goToPayStackScreen() {
    Get.to(() => PayStackWebPaymentScreen());
  }

  goToBkashScreen() {
    Get.to(() => BkashWebPaymentScreen());
  }

  goToSslScreen() {
    Get.toNamed(Routes.sslWebPaymentScreen);
  }

  goToRazorPayScreen() {
    Get.toNamed(Routes.razorPayScreen);
  }

  goToPagaditoWebPaymentScreen() {
    Get.toNamed(Routes.pagaditoWebPaymentScreen);
  }

  goToManualSendMoneyManualScreen() {
    Get.toNamed(Routes.sendMoneyManualPaymentScreen);
  }

  // ---------------------------- AddMoneyManualInsertModel -----------------

  late AddMoneyManualInsertModel _addMoneyManualInsertModel;

  AddMoneyManualInsertModel get addMoneyManualInsertModel =>
      _addMoneyManualInsertModel;

  // --------------------------- Api function ----------------------------------
  // Manual Payment Get Gateway process function
  Future<AddMoneyManualInsertModel> manualPaymentGetGatewaysProcess() async {
    _isInsertLoading.value = true;
    inputFields.clear();
    listImagePath.clear();
    listFieldName.clear();
    inputFieldControllers.clear();
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyManualInsertApi(body: inputBody).then((value) {
      _addMoneyManualInsertModel = value!;

      final previewData = _addMoneyManualInsertModel.data.paymentInformations;
      enteredAmount = previewData.requestAmount;
      transferFeeAmount = previewData.totalCharge;
      totalCharge = previewData.totalCharge;
      youWillGet = previewData.willGet;
      payableAmount = previewData.payableAmount;

      //-------------------------- Process inputs start ------------------------
      final data = _addMoneyManualInsertModel.data.inputFields;

      for (int item = 0; item < data.length; item++) {
        // make the dynamic controller
        var textEditingController = TextEditingController();
        inputFieldControllers.add(textEditingController);

        // make dynamic input widget
        if (data[item].type.contains('file')) {
          hasFile.value = true;
          inputFields.add(
            Padding(
              padding: const EdgeInsets.only(bottom: 8.0),
              child: ManualPaymentImageWidget(
                labelName: data[item].label,
                fieldName: data[item].name,
              ),
            ),
          );
        } else if (data[item].type.contains('text') ||
            data[item].type.contains('textarea')) {
          inputFields.add(
            Column(
              children: [
                PrimaryInputWidget(
                  paddings: EdgeInsets.only(
                      left: Dimensions.widthSize,
                      right: Dimensions.widthSize,
                      top: Dimensions.heightSize * 0.5),
                  controller: inputFieldControllers[item],
                  label: data[item].label,
                  hint: data[item].label,
                  isValidator: data[item].required,
                  inputFormatters: [
                    LengthLimitingTextInputFormatter(
                      int.parse(data[item].validation.max.toString()),
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize * 0.8),
              ],
            ),
          );
        }
        // final selectedIDType = "".obs;
        // List<IdTypeModel> idTypeList = [];
        else if (data[item].type.contains('select')) {
          hasFile.value = true;
          selectedIDType.value = data[item].validation.options.first.toString();
          inputFieldControllers[item].text = selectedIDType.value;
          for (var element in data[item].validation.options) {
            idTypeList.add(IdTypeModel(element, element));
          }
          inputFields.add(
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Obx(() => CustomDropDown<IdTypeModel>(
                    items: idTypeList,
                    title: data[item].label,
                    hint: selectedIDType.value.isEmpty
                        ? Strings.selectType
                        : selectedIDType.value,
                    onChanged: (value) {
                      selectedIDType.value = value!.title;
                    },
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.paddingHorizontalSize * 0.25,
                    ),
                    titleTextColor:
                        CustomColor.primaryLightTextColor.withOpacity(.2),
                    borderEnable: true,
                    dropDownFieldColor: Colors.transparent,
                    dropDownIconColor:
                        CustomColor.primaryLightTextColor.withOpacity(.2))),
                verticalSpace(Dimensions.marginBetweenInputBox * .8),
              ],
            ),
          );
        }
      }

      //-------------------------- Process inputs end --------------------------
      _isInsertLoading.value = false;
      gotoPreview();
      update();
    }).catchError((onError) {
      _isInsertLoading.value = false;
      log.e(onError);
    });

    update();
    return _addMoneyManualInsertModel;
  }

  // ---------------------------- manualPaymentProcess -------------------------

  final _isConfirmManualLoading = false.obs;

  bool get isConfirmManualLoading => _isConfirmManualLoading.value;

  late CommonSuccessModel _manualPaymentConfirmModel;

  CommonSuccessModel get manualPaymentConfirmModel =>
      _manualPaymentConfirmModel;

  Future<CommonSuccessModel> manualPaymentProcess() async {
    _isConfirmManualLoading.value = true;
    Map<String, String> inputBody = {
      'track': addMoneyManualInsertModel.data.paymentInformations.trx,
    };

    final data = addMoneyManualInsertModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    await ApiServices.manualPaymentConfirmApi(
            body: inputBody, fieldList: listFieldName, pathList: listImagePath)
        .then((value) {
      _manualPaymentConfirmModel = value!;
      _isConfirmManualLoading.value = false;
      update();
      // Get.offAndToNamed(Routes.bottomNavBarScreen);
      StatusScreen.show(
        context: Get.context!,
        subTitle: Strings.yourmoneyDepositSuccess.tr,
        onPressed: () {
          Get.offAllNamed(Routes.bottomNavBarScreen);
        },
      );
    }).catchError((onError) {
      log.e(onError);
    });
    _isConfirmManualLoading.value = false;
    update();
    return _manualPaymentConfirmModel;
  }

// keyboard fuction
  final amountTextController = TextEditingController();
  List<String> totalAmount = [];

  RxString selectCurrency = 'USD'.obs;
  RxString selectWallet = 'Paypal'.obs;

  @override
  void dispose() {
    amountTextController.dispose();

    super.dispose();
  }

  RxString selectItem = ''.obs;
  List<String> keyboardItemList = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '.',
    '0',
    '<'
  ];

  inputItem(int index) {
    return InkWell(
      overlayColor: WidgetStateProperty.all(Colors.transparent),
      onLongPress: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.clear();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        }
      },
      onTap: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.removeLast();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        } else {
          if (totalAmount.contains('.') && index == 9) {
            return;
          } else {
            totalAmount.add(keyboardItemList[index]);
            amountTextController.text = totalAmount.join('');
            debugPrint(totalAmount.join(''));
          }
        }
      },
      child: Center(
        child: CustomTitleHeadingWidget(
          text: keyboardItemList[index],
          style: Get.isDarkMode
              ? CustomStyle.lightHeading2TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize3 * 2,
                )
              : CustomStyle.darkHeading2TextStyle.copyWith(
                  color: CustomColor.primaryLightColor,
                  fontSize: Dimensions.headingTextSize3 * 2,
                ),
        ),
      ),
    );
  }

  /// Tatum payment gateway
  late TatumGatewayModel _tatumGatewayModel;

  TatumGatewayModel get tatumGatewayModel => _tatumGatewayModel;

  // add money tatum
  Future<TatumGatewayModel> tatumProcess() async {
    inputFields.clear();
    inputFieldControllers.clear();
    _isInsertLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.tatumInsertApi(body: inputBody).then((value) {
      _tatumGatewayModel = value!;

      final data = _tatumGatewayModel.data.gatewayInfo.addressInfo.inputFields;
      qrAddress.value = _tatumGatewayModel.data.gatewayInfo.addressInfo.address;
      for (int item = 0; item < data.length; item++) {
        // make the dynamic controller
        var textEditingController = TextEditingController();
        inputFieldControllers.add(textEditingController);

        if (data[item].type.contains('text')) {
          inputFields.add(
            Column(
              children: [
                PrimaryInputWidget(
                  controller: inputFieldControllers[item],
                  label: data[item].label,
                  hint: data[item].label,
                  isValidator: data[item].required,
                  inputFormatters: [
                    LengthLimitingTextInputFormatter(
                      int.parse(data[item].validation.max.toString()),
                    ),
                  ],
                ),
                verticalSpace(Dimensions.heightSize),
              ],
            ),
          );
        }
      }
      final paymentInfo = _tatumGatewayModel.data.paymentInfo;
      enteredAmount = paymentInfo.requestAmount;
      transferFeeAmount = paymentInfo.totalCharge;
      totalCharge = paymentInfo.totalCharge;
      youWillGet = paymentInfo.willGet;
      payableAmount = paymentInfo.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _tatumGatewayModel;
  }

  final _isTatumConfirmLoading = false.obs;
  bool get isTatumConfirmLoading => _isTatumConfirmLoading.value;
  late CommonSuccessModel _addMoneyConfirm;
  CommonSuccessModel get addMoneyConfirm => _addMoneyConfirm;

  // Profile TatumConfirm process with image
  Future<CommonSuccessModel> tatumConfirmProcess(BuildContext context) async {
    _isTatumConfirmLoading.value = true;
    update();

    Map<String, String> inputBody = {};
    final data = _tatumGatewayModel.data.gatewayInfo.addressInfo.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    await ApiServices.tatumConfirmApiProcess(
      body: inputBody,
      url: _tatumGatewayModel.data.gatewayInfo.addressInfo.submitUrl,
    ).then((value) {
      _addMoneyConfirm = value!;

      if (context.mounted) {
        StatusScreen.show(
          context: context,
          subTitle: Strings.yourMoneyAddedSucces.tr,
          onPressed: () {
            Get.offAllNamed(Routes.bottomNavBarScreen);
          },
        );
      }
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isTatumConfirmLoading.value = false;
    update();
    return _addMoneyConfirm;
  }

  void goToTatumScreen() {
    Get.toNamed(Routes.tatumPaymentScreen);
  }

  // Paystack

  late AddMoneyRazorPayInsertModel _payStackModel;

  AddMoneyRazorPayInsertModel get payStackModel => _payStackModel;

  Future<AddMoneyRazorPayInsertModel> addMoneyPayStackInsertProcess() async {
    _isInsertLoading.value = true;
    update();
    Map<String, dynamic> inputBody = {
      'amount': amountTextController.text,
      'currency': selectedCurrencyAlias.value,
    };

    await ApiServices.addMoneyInsertRazorPayApi(body: inputBody).then((value) {
      _payStackModel = value!;
      final data = _payStackModel.data.paymentInformations;
      enteredAmount = data.requestAmount;
      transferFeeAmount = data.totalCharge;
      totalCharge = data.totalCharge;
      youWillGet = data.willGet;
      payableAmount = data.payableAmount;
      gotoPreview();
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isInsertLoading.value = false;
    update();
    return _payStackModel;
  }
}
